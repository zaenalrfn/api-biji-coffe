<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    /**
     * Promote a user to admin.
     */
    public function promote(User $user)
    {
        // Add the admin role to the user
        $user->assignRole('admin');

        return response()->json([
            'message' => 'User promoted to admin successfully.',
            'user' => $user->fresh()->load('roles'), // Reload user with roles
        ]);
    }
}
