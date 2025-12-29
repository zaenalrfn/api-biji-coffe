<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;

class AdminOrderController extends Controller
{
    /**
     * Display a listing of all orders.
     */
    public function index()
    {
        $orders = Order::with(['user', 'items.product'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($orders);
    }

    /**
     * Update the specified order status.
     */
    public function update(Request $request, $id)
    {
        $order = Order::find($id);

        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        $request->validate([
            'status' => 'nullable|string|in:pending,processing,completed,cancelled',
            'payment_status' => 'nullable|string|in:unpaid,paid,failed',
        ]);

        $order->update($request->only(['status', 'payment_status']));

        return response()->json([
            'message' => 'Order updated successfully',
            'data' => $order
        ]);
    }

    /**
     * Remove the specified order from storage.
     */
    public function destroy($id)
    {
        $order = Order::find($id);

        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        $order->delete();

        return response()->json(['message' => 'Order deleted successfully']);
    }

    /**
     * Assign a driver to an order.
     */
    public function assignDriver(Request $request, $id)
    {
        $request->validate([
            'driver_id' => 'required|exists:drivers,id',
        ]);

        $order = Order::findOrFail($id);
        $order->update([
            'driver_id' => $request->driver_id,
            'status' => 'confirmed' // Assuming assigning driver confirms it or moves it forward
        ]);

        $driver = \App\Models\Driver::find($request->driver_id);
        if ($driver && $driver->user) {
            // Notify Driver's User account
            $driver->user->sendNotification(
                'New Order Assigned',
                'You have been assigned to order #' . $order->id,
                'order_assigned',
                $order->id
            );
        }

        // Also notify the customer ?
        if ($order->user) {
            $order->user->sendNotification(
                'Driver Assigned',
                'Driver ' . $driver->name . ' has been assigned to your order.',
                'order_update',
                $order->id
            );
        }

        return response()->json([
            'message' => 'Driver assigned successfully',
            'data' => $order
        ]);
    }
}
