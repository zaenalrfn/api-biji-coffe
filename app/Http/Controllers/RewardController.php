<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RewardController extends Controller
{
    /**
     * Get rewards page data including points, challenges, and available coupons
     */
    public function index()
    {
        $user = Auth::user();

        // Get user's available coupons (active and not expired)
        $availableCoupons = Coupon::where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->get();

        // Weekly Coffee Challenge - target is 5 orders per week
        $weeklyOrders = $user->orders()
            ->where('status', 'completed')
            ->where('created_at', '>=', now()->startOfWeek())
            ->count();

        return response()->json([
            'points' => $user->points,
            'unit' => 'PBC',
            'challenge' => [
                'title' => 'Weekly Coffee Challenge',
                'description' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et',
                'progress' => $weeklyOrders,
                'target' => 5,
                'orders_left' => max(0, 5 - $weeklyOrders),
            ],
            'available_coupons' => $availableCoupons,
        ]);
    }
}
