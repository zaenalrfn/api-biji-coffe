<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MidtransCallbackController extends Controller
{
    public function handle(Request $request)
    {
        Log::info('Midtrans Callback Received', $request->all());

        $serverKey = env('MIDTRANS_SERVER_KEY');
        // SHA512(order_id+status_code+gross_amount+ServerKey)
        $hashed = hash("sha512", $request->order_id . $request->status_code . $request->gross_amount . $serverKey);

        Log::info('Midtrans Signature Check', [
            'hashed' => $hashed,
            'request_signature' => $request->signature_key,
            'match' => $hashed == $request->signature_key,
            'string_to_hash' => $request->order_id . $request->status_code . $request->gross_amount . $serverKey
        ]);

        if ($hashed == $request->signature_key) {
            // Find order by transaction_id (which is sent as order_id to Midtrans)
            $order = Order::where('transaction_id', $request->order_id)->first();

            if (!$order) {
                Log::error('Midtrans Callback: Order not found', ['order_id' => $request->order_id]);
                return response()->json(['message' => 'Order not found'], 404);
            }

            Log::info('Midtrans Order Found', ['id' => $order->id, 'current_status' => $order->status]);

            if ($request->transaction_status == 'capture' || $request->transaction_status == 'settlement') {
                $order->update([
                    'status' => 'processing',
                    'payment_status' => 'paid',
                ]);
                Log::info('Midtrans Order Updated to Processing', ['id' => $order->id]);
            } elseif ($request->transaction_status == 'expire' || $request->transaction_status == 'cancel' || $request->transaction_status == 'deny') {
                $order->update([
                    'status' => 'cancelled',
                    'payment_status' => 'failed',
                ]);
                Log::info('Midtrans Order Updated to Cancelled', ['id' => $order->id]);
            } elseif ($request->transaction_status == 'pending') {
                $order->update([
                    'status' => 'pending',
                    'payment_status' => 'unpaid',
                ]);
                Log::info('Midtrans Order Updated to Pending', ['id' => $order->id]);
            }

            return response()->json(['message' => 'Success']);
        }

        Log::warning('Midtrans Invalid Signature', [
            'expected' => $hashed,
            'received' => $request->signature_key
        ]);
        return response()->json(['message' => 'Invalid signature'], 403);
    }
}
