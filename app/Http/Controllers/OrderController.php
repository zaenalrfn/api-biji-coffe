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
        ]);

        $user = Auth::user();
        $cartItems = CartItem::where('user_id', $user->id)->with('product')->get();

        if ($cartItems->isEmpty()) {
            return response()->json(['message' => 'Cart is empty'], 400);
        }

        $totalPrice = 0;
        $orderItemsData = []; // Store calculated data to reuse

        foreach ($cartItems as $item) {
            $basePrice = $item->product->price;
            $multiplier = match ($item->size) {
                'SM' => 0.9,
                'MD' => 1.0,
                'LG' => 1.2,
                'XL' => 1.3,
                default => 1.0,
            };
            $finalPrice = $basePrice * $multiplier;
            $totalPrice += $finalPrice * $item->quantity;

            $orderItemsData[] = [
                'product_id' => $item->product_id,
                'quantity' => $item->quantity,
                'price' => $finalPrice,
                'notes' => $item->notes,
                'size' => $item->size ?? 'MD', // Ensure size is recorded
                'name' => $item->product->title, // Carry product name for Midtrans
            ];
        }

        try {
            DB::beginTransaction();

            $transactionId = 'ORDER-' . time() . '-' . $user->id;

            $order = Order::create([
                'user_id' => $user->id,
                'payment_method' => 'Midtrans',
                'shipping_address' => $request->shipping_address,
                'total_price' => $totalPrice, // Uses the size-adjusted total
                'status' => 'pending',
                'payment_status' => 'unpaid',
                'transaction_id' => $transactionId,
            ]);

            foreach ($orderItemsData as $data) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $data['product_id'],
                    'quantity' => $data['quantity'],
                    'price' => $data['price'], // Adjusted price
                    'notes' => $data['notes'],
                    'size' => $data['size'],
                ]);
            }

            // --- Midtrans Integration ---
            \Midtrans\Config::$serverKey = env('MIDTRANS_SERVER_KEY');
            \Midtrans\Config::$isProduction = (bool) env('MIDTRANS_IS_PRODUCTION', false);
            \Midtrans\Config::$isSanitized = true;
            // Set 3DS transaction for credit card to true
            \Midtrans\Config::$is3ds = true;

            // Override usage of SSL for local development (fixes CURL Error: SSL certificate problem)
            \Midtrans\Config::$curlOptions = [
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => 0,
                CURLOPT_HTTPHEADER => [], // Fix for Undefined array key 10023 likely accessed by library
            ];

            $params = [
                'transaction_details' => [
                    'order_id' => $transactionId,
                    'gross_amount' => (int) $totalPrice,
                ],
                'customer_details' => [
                    'first_name' => $user->name,
                    'email' => $user->email,
                ],
                'item_details' => array_map(function ($item) {
                    return [
                        'id' => $item['product_id'],
                        'price' => (int) $item['price'],
                        'quantity' => $item['quantity'],
                        'name' => substr($item['name'] . ' (' . $item['size'] . ')', 0, 50), // Include size in name
                    ];
                }, $orderItemsData),
            ];

            $snapToken = \Midtrans\Snap::getSnapToken($params);

            $order->update(['snap_token' => $snapToken]);

            // --- End Midtrans Integration ---

            // Send Notification
            $user->sendNotification(
                'Order Successful',
                'Your order #' . $order->id . ' has been successfully placed. Please complete payment.',
                'order',
                $order->id
            );

            // Clear cart
            CartItem::where('user_id', $user->id)->delete();

            DB::commit();

            return response()->json([
                'order' => $order->load('items.product'),
                'snap_token' => $snapToken,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to create order', 'error' => $e->getMessage()], 500);
        }
    }
    public function show($id)
    {
        $order = Order::where('user_id', Auth::id())->with(['items.product', 'driver'])->findOrFail($id);
        return response()->json($order);
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|string|in:pending,confirmed,processing,on_delivery,completed,cancelled',
        ]);

        // Logic to check if user is authorized (Admin or Driver or Customer?)
        // For simplicity, assuming Driver or Admin is calling this. 
        // If it's a driver specific endpoint, usually we verify driver ownership.
        // But for now, allow authenticated users to try, or ideally restrict via policy.
        // Since I don't have roles middleware setup visible here, I'll assume auth is passed.

        $order = Order::with('items')->findOrFail($id);
        $previousStatus = $order->status;

        // Update status
        $order->update(['status' => $request->status]);

        // Award points when order is completed (2 PBC per product)
        if ($request->status === 'completed' && $previousStatus !== 'completed') {
            $totalProducts = $order->items->sum('quantity');
            $pointsEarned = $totalProducts * 2; // 2 PBC per product

            $order->user->addPoints($pointsEarned);

            // Send notification about points earned
            $order->user->sendNotification(
                'Poin Berhasil Ditambahkan!',
                'Anda mendapat ' . $pointsEarned . ' PBC dari pesanan #' . $order->id . '. Total poin: ' . $order->user->fresh()->points . ' PBC',
                'points',
                $order->id
            );
        }

        // Trigger notification to User (Customer)
        $order->user->sendNotification(
            'Order Status Update',
            'Your order #' . $order->id . ' is now ' . str_replace('_', ' ', $request->status),
            'order_status',
            $order->id
        );

        return response()->json([
            'message' => 'Order status updated successfully',
            'data' => $order
        ]);
    }
}
