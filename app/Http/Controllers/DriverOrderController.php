<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use Illuminate\Support\Facades\Auth;

class DriverOrderController extends Controller
{
    // Get orders assigned to the logged-in driver
    public function index()
    {
        $user = Auth::user();

        // Find driver profile associated with user
        $driver = $user->driver;

        if (!$driver) {
            return response()->json([
                'status' => 'error',
                'message' => 'User is not a registered driver'
            ], 403);
        }

        // Ambil order yang assigned ke driver ini DAN statusnya bukan cancelled
        $orders = Order::where('driver_id', $driver->id)
            ->whereIn('status', ['confirmed', 'processing', 'on_delivery', 'completed'])
            ->orderBy('created_at', 'desc')
            ->get();
        return response()->json(['data' => $orders]);
    }

    // Update status order (Ambil -> Antar -> Selesai)
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:processing,on_delivery,completed'
        ]);

        $user = Auth::user();
        $driver = $user->driver;

        if (!$driver) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $order = Order::with('items')->where('id', $id)->where('driver_id', $driver->id)->firstOrFail();
        $previousStatus = $order->status;
        $order->status = $request->status;
        $order->save();

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

        // Optional: Notify customer
        if ($order->user) {
            $statusNice = str_replace('_', ' ', $order->status);
            $order->user->sendNotification(
                'Order Update',
                "Your order status is now: " . ucfirst($statusNice),
                'order_update',
                $order->id
            );
        }

        return response()->json(['message' => 'Status updated', 'data' => $order]);
    }
}
