<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class OauthController extends Controller
{
    public function redirectToProvider($provider)
    {
        return Socialite::driver($provider)->stateless()->redirect();
    }

    public function handleProviderCallback($provider)
    {
        try {
            $driver = Socialite::driver($provider)->stateless();
            $driver->setHttpClient(new \GuzzleHttp\Client(['verify' => false])); // Bypass SSL for Localhost/Windows
            $socialUser = $driver->user();

            // Determine appropriate ID column
            $idColumn = $provider . '_id'; // google_id or facebook_id

            // Find user by Email
            $user = User::where('email', $socialUser->getEmail())->first();

            if ($user) {
                // If user exists, update Provider ID if missing
                if (!$user->$idColumn) {
                    $user->update([$idColumn => $socialUser->getId()]);
                }
            } else {
                // Create new user
                $user = User::create([
                    'name' => $socialUser->getName(),
                    'email' => $socialUser->getEmail(),
                    'password' => bcrypt(Str::random(16)),
                    $idColumn => $socialUser->getId(),
                    'email_verified_at' => now(),
                ]);
            }

            // Assign default role if using Spatie
            if ($user->roles->isEmpty()) {
                $user->assignRole('user');
            }

            // Create Token
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'message' => 'Login success',
                'access_token' => $token,
                'token_type' => 'Bearer',
                'user' => $user
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Login failed: ' . $e->getMessage()], 500);
        }
    }
}
