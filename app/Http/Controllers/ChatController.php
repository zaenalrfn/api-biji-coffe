<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderMessage;
use App\Events\MessageSent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChatController extends Controller
{
    /**
     * KIRIM PESAN
     */
    public function sendMessage(Request $request, $id)
    {
        $request->validate([
            'message' => 'required|string'
        ]);

        $order = Order::with(['user', 'driver.user'])->findOrFail($id);
        $sender = Auth::user();

        // Authorization (customer / driver saja)
        if (
            $sender->id !== $order->user_id &&
            $sender->id !== $order->driver?->user_id
        ) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Simpan pesan
        $message = OrderMessage::create([
            'order_id' => $order->id,
            'sender_id' => $sender->id,
            'message' => $request->message,
        ]);

        // Load sender (buat Flutter)
        $message->load('sender:id,name');

        // 🔥 REALTIME REVERB
        broadcast(new MessageSent($message))->toOthers();

        return response()->json($message, 201);
    }

    /**
     * AMBIL CHAT DETAIL
     */
    public function getMessages($id)
    {
        $order = Order::with('driver')->findOrFail($id);

        if (
            Auth::id() !== $order->user_id &&
            Auth::id() !== $order->driver?->user_id
        ) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return OrderMessage::where('order_id', $order->id)
            ->with('sender:id,name')
            ->orderBy('created_at')
            ->get();
    }

    /**
     * CHAT LIST
     */
    public function getChatList()
    {
        $user = Auth::user();

        $orders = Order::with([
            'user',
            'driver.user',
            'messages' => fn($q) => $q->latest()
        ])
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->orWhereHas(
                        'driver',
                        fn($d) =>
                        $d->where('user_id', $user->id)
                    );
            })
            ->has('messages')
            ->get()
            ->map(function ($order) use ($user) {
                // Determine if the current user is the customer
                $isCustomer = $user->id === $order->user_id;

                if ($isCustomer) {
                    // If I am customer, show Driver details
                    $name = $order->driver?->name ?? $order->driver?->user?->name ?? 'Unknown Driver';
                    $avatar = $order->driver?->photo_url
                        ? asset('storage/' . $order->driver->photo_url)
                        : ($order->driver?->user?->profile_photo_path ? asset('storage/' . $order->driver->user->profile_photo_path) : null);
                } else {
                    // If I am driver, show Customer details
                    $name = $order->user?->name ?? 'Unknown Customer';
                    $avatar = $order->user?->profile_photo_path
                        ? asset('storage/' . $order->user->profile_photo_path)
                        : null;
                }

                $last = $order->messages->first();

                return [
                    'order_id' => $order->id,
                    'name' => $name,
                    'avatar' => $avatar,
                    'last_message' => $last?->message ?? '',
                    'time' => $last?->created_at->diffForHumans() ?? '',
                ];
            });

        return response()->json($orders);
    }
}
