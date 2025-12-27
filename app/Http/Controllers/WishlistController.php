<?php

namespace App\Http\Controllers;

use App\Models\Wishlist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WishlistController extends Controller
{
    // List user's wishlist
    public function index()
    {
        $wishlists = Wishlist::where('user_id', Auth::id())
            ->with('product.category') // Eager load product details
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($wishlists);
    }

    // Add product to wishlist
    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
        ]);

        $user = Auth::user();

        // Check if already in wishlist
        $exists = Wishlist::where('user_id', $user->id)
            ->where('product_id', $request->product_id)
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'Product already in wishlist'], 409); // Conflict
        }

        $wishlist = Wishlist::create([
            'user_id' => $user->id,
            'product_id' => $request->product_id,
        ]);

        return response()->json($wishlist->load('product'), 201);
    }

    // Remove product from wishlist
    public function destroy($productId)
    {
        $deleted = Wishlist::where('user_id', Auth::id())
            ->where('product_id', $productId)
            ->delete();

        if ($deleted) {
            return response()->json(['message' => 'Removed from wishlist'], 200);
        }

        return response()->json(['message' => 'Item not found in wishlist'], 404);
    }
}
