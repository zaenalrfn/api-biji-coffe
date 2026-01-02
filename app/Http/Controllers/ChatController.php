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
            'order_id'  => $order->id,
            'sender_id' => $sender->id,
            'message'   => $request->message,
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
                'messages' => fn ($q) => $q->latest()
            ])
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhereHas('driver', fn ($d) =>
                      $d->where('user_id', $user->id)
                  );
            })
            ->has('messages')
            ->get()
            ->map(function ($order) use ($user) {
                $participant = $user->id === $order->user_id
                    ? $order->driver?->user
                    : $order->user;

                $last = $order->messages->first();

                return [
                    'order_id' => $order->id,
                    'name'     => $participant?->name ?? 'Unknown',
                    'avatar'   => $participant?->profile_photo_path
                        ? asset('storage/' . $participant->profile_photo_path)
                        : null,
                    'last_message' => $last?->message ?? '',
                    'time' => $last?->created_at->diffForHumans() ?? '',
                ];
            });

        return response()->json($orders);
    }
}
