<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use Illuminate\Http\Request;
use Carbon\Carbon;

class CouponController extends Controller
{
    /**
     * Display a listing of coupons (Admin).
     */
    public function index()
    {
        return response()->json(Coupon::orderBy('created_at', 'desc')->get());
    }

    /**
     * Store a newly created coupon in storage (Admin).
     */
    public function store(Request $request)
    {
        $request->validate([
            'code' => 'required|string|unique:coupons,code',
            'type' => 'required|in:fixed,percent',
            'value' => 'required|numeric|min:0',
            'min_purchase' => 'nullable|numeric|min:0',
            'expires_at' => 'nullable|date',
            'is_active' => 'boolean',
        ]);

        $coupon = Coupon::create($request->all());

        return response()->json([
            'message' => 'Coupon created successfully',
            'data' => $coupon
        ], 201);
    }

    /**
     * Remove the specified coupon from storage (Admin).
     */
    public function destroy($id)
    {
        $coupon = Coupon::find($id);

        if (!$coupon) {
            return response()->json(['message' => 'Coupon not found'], 404);
        }

        $coupon->delete();

        return response()->json(['message' => 'Coupon deleted successfully']);
    }

    /**
     * Check coupon validity (User/Public).
     */
    public function check(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
            'total_amount' => 'required|numeric|min:0',
        ]);

        $coupon = Coupon::where('code', $request->code)->first();

        if (!$coupon) {
            return response()->json(['message' => 'Invalid coupon code'], 404);
        }

        if (!$coupon->is_active) {
            return response()->json(['message' => 'Coupon is inactive'], 400);
        }

        if ($coupon->expires_at && Carbon::now()->greaterThan($coupon->expires_at)) {
            return response()->json(['message' => 'Coupon expired'], 400);
        }

        if ($request->total_amount < $coupon->min_purchase) {
            return response()->json(['message' => 'Minimum purchase not met. Min: ' . $coupon->min_purchase], 400);
        }

        // Calculate discount
        $discountAmount = 0;
        if ($coupon->type === 'fixed') {
            $discountAmount = $coupon->value;
        } else {
            $discountAmount = ($coupon->value / 100) * $request->total_amount;
        }

        // Ensure discount doesn't exceed total
        $discountAmount = min($discountAmount, $request->total_amount);

        return response()->json([
            'message' => 'Coupon applied',
            'data' => [
                'code' => $coupon->code,
                'discount_amount' => $discountAmount,
                'final_price' => $request->total_amount - $discountAmount
            ]
        ]);
    }
}
