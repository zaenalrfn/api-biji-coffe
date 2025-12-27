<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\CartItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $orders = Order::where('user_id', Auth::id())
            ->with(['items.product'])
            ->orderBy('created_at', 'desc')
            ->get();
        return response()->json($orders);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'shipping_address' => 'required|array',
            'shipping_address.recipient_name' => 'required|string',
            'shipping_address.address_line' => 'required|string',
            'shipping_address.city' => 'required|string',
            'shipping_address.state' => 'required|string',
            'shipping_address.country' => 'required|string',
            'shipping_address.zip_code' => 'required|string',
            // 'payment_method' => 'required|string|in:Credit Card,Bank Transfer,Virtual Account', // No longer strictly needed as Midtrans handles method
        ]);

        $user = Auth::user();
        $cartItems = CartItem::where('user_id', $user->id)->with('product')->get();

        if ($cartItems->isEmpty()) {
            return response()->json(['message' => 'Cart is empty'], 400);
        }

        $totalPrice = 0;
        foreach ($cartItems as $item) {
            $totalPrice += $item->product->price * $item->quantity;
        }

        try {
            DB::beginTransaction();

            $order = Order::create([
                'user_id' => $user->id,
                'payment_method' => 'Midtrans', // Defaulting to Midtrans
                'shipping_address' => $request->shipping_address,
                'total_price' => $totalPrice,
                'status' => 'pending',
                'payment_status' => 'unpaid',
            ]);

            foreach ($cartItems as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'price' => $item->product->price,
                    'notes' => $item->notes,
                ]);
            }

            // --- Midtrans Integration ---
            // Set your Merchant Server Key
            \Midtrans\Config::$serverKey = env('MIDTRANS_SERVER_KEY');
            // Set to Development/Sandbox Environment (default). Set to true for Production Environment (accept real transaction).
            \Midtrans\Config::$isProduction = (bool) env('MIDTRANS_IS_PRODUCTION', false);
            // Set sanitization on (default)
            \Midtrans\Config::$isSanitized = true;
            // Set 3DS transaction for credit card to true
            \Midtrans\Config::$is3ds = true;

            $params = [
                'transaction_details' => [
                    'order_id' => $order->id . '-' . time(), // Unique ID
                    'gross_amount' => (int) $order->total_price,
                ],
                'customer_details' => [
                    'first_name' => $user->name,
                    'email' => $user->email,
                ],
                'item_details' => $cartItems->map(function ($item) {
                    return [
                        'id' => $item->product_id,
                        'price' => (int) $item->product->price,
                        'quantity' => $item->quantity,
                        'name' => substr($item->product->title, 0, 50),
                    ];
                })->toArray(),
            ];

            // Get Snap Token
            $snapToken = \Midtrans\Snap::getSnapToken($params);
            // Get Payment URL (optional, if using redirect directly)
            // $paymentUrl = \Midtrans\Snap::createTransaction($params)->redirect_url;

            // --- End Midtrans Integration ---

            // Clear cart
            CartItem::where('user_id', $user->id)->delete();

            DB::commit();

            return response()->json([
                'order' => $order->load('items.product'),
                'snap_token' => $snapToken,
                // 'payment_url' => $paymentUrl ?? null,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to create order', 'error' => $e->getMessage()], 500);
        }
    }
}
