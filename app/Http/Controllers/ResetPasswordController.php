<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Mail\ResetPasswordOtpMail;

class ResetPasswordController extends Controller
{
    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            // For security, do not reveal if user does not exist
            return response()->json(['message' => 'If your email is registered, you will receive an OTP code shortly.'], 200);
        }

        // Generate 6 Digit OTP
        $otp = rand(100000, 999999);

        // Store OTP in password_reset_tokens
        // Delete old token for this email first
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        DB::table('password_reset_tokens')->insert([
            'email' => $request->email,
            'token' => $otp, // Storing plain text for simplicity/OTP usage
            'created_at' => now()
        ]);

        // Send Email
        Mail::to($user->email)->send(new ResetPasswordOtpMail($otp, $user));

        return response()->json(['message' => 'OTP sent to your email.'], 200);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        // Verify OTP
        $record = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->where('token', $request->otp) // Case sensitive search usually generally okay for digits
            ->first();

        if (!$record) {
            return response()->json(['message' => 'Invalid OTP'], 400);
        }

        // Check Expiry (e.g., 15 minutes)
        if (now()->diffInMinutes($record->created_at) > 15) {
            DB::table('password_reset_tokens')->where('email', $request->email)->delete();
            return response()->json(['message' => 'OTP Expired'], 400);
        }

        // Reset Password
        $user = User::where('email', $request->email)->firstOrFail();
        $user->update(['password' => Hash::make($request->password)]);

        // Delete Token
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        // Optional: Revoke all tokens to force re-login on all devices
        $user->tokens()->delete();

        return response()->json(['message' => 'Password reset successfully. Please login with your new password.'], 200);
    }
}
