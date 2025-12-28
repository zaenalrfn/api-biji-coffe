<?php

namespace App\Http\Controllers;


use Laravel\Socialite\Facades\Socialite;
use Exception;
use App\Models\User;

class OauthController extends Controller
{
    public function redirectToProvider()
    {
        /** @var \Laravel\Socialite\Two\AbstractProvider $driver */
        $driver = Socialite::driver('google');

        // Stateless is required for API stateless authentication
        return $driver->stateless()->redirect();
    }

    // public function handleProviderCallback()
    // {
    //     try {
    //         /** @var \Laravel\Socialite\Two\AbstractProvider $driver */
    //         $driver = Socialite::driver('google');

    //         // Use stateless() here as well
    //         $user = $driver->stateless()->user();

    //         $finduser = User::where('gauth_id', $user->id)->first();

    //         if ($finduser) {
    //             // If user exists, create token
    //             $token = $finduser->createToken('auth_token')->plainTextToken;

    //             return response()->json([
    //                 'message' => 'Login successful',
    //                 'access_token' => $token,
    //                 'token_type' => 'Bearer',
    //                 'user' => $finduser
    //             ], 200);

    //         } else {
    //             // If user does not exist, create new user
    //             $newUser = User::create([
    //                 'name' => $user->name,
    //                 'email' => $user->email,
    //                 'gauth_id' => $user->id,
    //                 'gauth_type' => 'google',
    //                 // Random password for security since they log in via Google
    //                 'password' => encrypt('admin@123')
    //             ]);

    //             // Assign default role 'user'
    //             $newUser->assignRole('user');

    //             $token = $newUser->createToken('auth_token')->plainTextToken;

    //             return response()->json([
    //                 'message' => 'User created and logged in successfully',
    //                 'access_token' => $token,
    //                 'token_type' => 'Bearer',
    //                 'user' => $newUser
    //             ], 201);
    //         }

    //     } catch (Exception $e) {
    //         return response()->json([
    //             'message' => 'Authentication failed',
    //             'error' => $e->getMessage()
    //         ], 500);
    //     }
    // }
    public function handleProviderCallback()
    {
        try {
            $driver = Socialite::driver('google');
            $user = $driver->stateless()->user();
            $finduser = User::where('gauth_id', $user->id)->first();
            // Create or Get User logic (Simplified for brevity, keep your existing creation logic)
            if (!$finduser) {
                $finduser = User::create([
                    'name' => $user->name,
                    'email' => $user->email,
                    'gauth_id' => $user->id,
                    'gauth_type' => 'google',
                    'password' => encrypt('admin@123')
                ]);
                $finduser->assignRole('user');
            }
            // Create Token
            $token = $finduser->createToken('auth_token')->plainTextToken;
            // CHECK REQUEST TYPE
            // If it's an AJAX/API request (Mobile mostly), return JSON
            if (request()->wantsJson()) {
                return response()->json([
                    'message' => 'Login successful',
                    'access_token' => $token,
                    'token_type' => 'Bearer',
                    'user' => $finduser
                ], 200);
            } else {
                // WEB REDIRECT FLOW
                // Redirect back to your Flutter Web App
                // CHANGE 'http://localhost:XYZ' to your actual Flutter Web URL
                $frontendUrl = "http://localhost:5555/?token=" . $token;
                return redirect($frontendUrl);
            }
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
