<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
        ]);

        $user = User::create([
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
            'password' => Hash::make($validatedData['password']),
        ]);

        $user->assignRole('user');

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => array_merge($user->toArray(), ['roles' => $user->getRoleNames()]),
        ]);
    }

    public function login(Request $request)
    {
        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'message' => 'Invalid login details'
            ], 401);
        }

        $user = User::where('email', $request['email'])->firstOrFail();

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => array_merge($user->toArray(), ['roles' => $user->getRoleNames()]),
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Successfully logged out'
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'image' => 'nullable|image|max:2048', // Validate image
        ]);

        $user->name = $validatedData['name'];
        $user->email = $validatedData['email'];

        // Handle Image Upload
        if ($request->hasFile('image')) {
            // Delete old photo if exists and isn't a URL/external
            if ($user->profile_photo_path && Storage::disk('public')->exists($user->profile_photo_path)) {
                Storage::disk('public')->delete($user->profile_photo_path);
            }

            $path = $request->file('image')->store('profile-photos', 'public');
            $user->profile_photo_path = $path;
        }

        $user->save();

        $user->sendNotification(
            'Profile Updated',
            'Your profile information has been updated successfully.',
            'account'
        );

        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => array_merge($user->toArray(), ['roles' => $user->getRoleNames()]),
        ]);
    }

    public function loginGuest()
    {
        $uuid = (string) Str::uuid();

        $user = User::create([
            'name' => 'Guest User',
            'email' => 'guest_' . $uuid . '@bijicoffee.com',
            'password' => Hash::make(Str::random(16)),
            'is_guest' => true,
            'email_verified_at' => now(), // Auto verify for guest
        ]);

        $user->assignRole('user');

        $token = $user->createToken('guest_token')->plainTextToken;

        return response()->json([
            'message' => 'Logged in as Guest',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => array_merge($user->toArray(), ['roles' => $user->getRoleNames()]),
        ]);
    }
}
