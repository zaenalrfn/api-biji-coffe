<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MidtransCallbackController extends Controller
{
    public function handle(Request $request)
    {
        $serverKey = env('MIDTRANS_SERVER_KEY');
        // SHA512(order_id+status_code+gross_amount+ServerKey)
        $hashed = hash("sha512", $request->order_id . $request->status_code . $request->gross_amount . $serverKey);

        if ($hashed == $request->signature_key) {
            // Order ID format could be "ID-Timestamp", so we split slightly differently or just find by ID if we stored it as string
            $orderId = explode('-', $request->order_id)[0];
            $order = Order::find($orderId);

            if (!$order) {
                return response()->json(['message' => 'Order not found'], 404);
            }

            if ($request->transaction_status == 'capture' || $request->transaction_status == 'settlement') {
                $order->update([
                    'status' => 'processing',
                    'payment_status' => 'paid',
                ]);
            } elseif ($request->transaction_status == 'expire' || $request->transaction_status == 'cancel' || $request->transaction_status == 'deny') {
                $order->update([
                    'status' => 'cancelled',
                    'payment_status' => 'failed',
                ]);
            } elseif ($request->transaction_status == 'pending') {
                $order->update([
                    'status' => 'pending',
                    'payment_status' => 'unpaid',
                ]);
            }

            return response()->json(['message' => 'Success']);
        }

        return response()->json(['message' => 'Invalid signature'], 403);
    }
}
