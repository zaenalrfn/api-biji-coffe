<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PointController extends Controller
{
    /**
     * Get current user's points balance
     */
    public function index()
    {
        $user = Auth::user();

        return response()->json([
            'points' => $user->points,
            'unit' => 'PBC',
        ]);
    }
}
