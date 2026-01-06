<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CartController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $cartItems = CartItem::where('user_id', Auth::id())->with('product')->get();
        return response()->json($cartItems);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'notes' => 'nullable|string',
            'size' => 'nullable|in:SM,MD,LG,XL', // Validate size
        ]);

        $size = $request->size ?? 'MD'; // Default size

        $cartItem = CartItem::updateOrCreate(
            [
                'user_id' => Auth::id(),
                'product_id' => $request->product_id,
                'size' => $size, // Unique item per size
            ],
            [
                'quantity' => \DB::raw('quantity + ' . $request->quantity),
                'notes' => $request->notes,
            ]
        );

        return response()->json($cartItem, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $cartItem = CartItem::where('user_id', Auth::id())->with('product')->findOrFail($id);
        return response()->json($cartItem);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'quantity' => 'integer|min:1',
            'notes' => 'nullable|string',
            'size' => 'nullable|in:SM,MD,LG,XL',
        ]);

        $cartItem = CartItem::where('user_id', Auth::id())->findOrFail($id);

        $cartItem->update($request->only('quantity', 'notes', 'size'));

        return response()->json($cartItem);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $cartItem = CartItem::where('user_id', Auth::id())->findOrFail($id);
        $cartItem->delete();

        return response()->json(null, 204);
    }
}
