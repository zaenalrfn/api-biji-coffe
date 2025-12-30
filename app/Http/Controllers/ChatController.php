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
     * Kirim pesan chat
     */
    public function sendMessage(Request $request, $id)
    {
        $request->validate([
            'message' => 'required|string'
        ]);

        // Ambil order + relasi
        $order = Order::with(['user', 'driver.user'])->findOrFail($id);
        $sender = Auth::user();

        // Tentukan penerima
        $recipient = null;

        if ($sender->id === $order->user_id) {
            // Pengirim adalah customer
            $recipient = $order->driver?->user;
        } elseif ($sender->id === $order->driver?->user_id) {
            // Pengirim adalah driver
            $recipient = $order->user;
        }

        if (!$recipient) {
            return response()->json([
                'message' => 'Chat belum tersedia (Driver belum di-assign)'
            ], 400);
        }

        // Simpan pesan
        $message = OrderMessage::create([
            'order_id'  => $order->id,
            'sender_id' => $sender->id,
            'message'   => $request->message
        ]);

        // Load sender untuk realtime
        $message->load('sender:id,name');

        // Broadcast realtime
        broadcast(new MessageSent($message))->toOthers();

        // Kirim notifikasi
        $recipient->sendNotification(
            'Pesan baru dari ' . $sender->name,
            $request->message,
            'chat_message',
            $order->id
        );

        return response()->json($message, 201);
    }

    /**
     * Ambil semua pesan dalam satu order
     */
    public function getMessages($id)
    {
        $order = Order::with('driver')->findOrFail($id);

        $driverUserId = $order->driver?->user_id;

        // Authorization
        if (
            Auth::id() !== $order->user_id &&
            Auth::id() !== $driverUserId
        ) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return OrderMessage::where('order_id', $order->id)
            ->with('sender:id,name')
            ->orderBy('created_at', 'asc')
            ->get();
    }

    /**
     * Ambil daftar chat (chat list)
     */
    public function getChatList()
    {
        $user = Auth::user();

        $orders = Order::with([
                'user',
                'driver.user',
                'messages' => function ($query) {
                    $query->latest();
                }
            ])
            ->where(function ($query) use ($user) {
                $query->where('user_id', $user->id)
                      ->orWhereHas('driver', function ($q) use ($user) {
                          $q->where('user_id', $user->id);
                      });
            })
            ->has('messages')
            ->get()
            ->map(function ($order) use ($user) {
                $participant = ($user->id === $order->user_id)
                    ? $order->driver?->user
                    : $order->user;

                $lastMessage = $order->messages->first();

                return [
                    'order_id'    => $order->id,
                    'name'        => $participant?->name ?? 'Unknown',
                    'avatar'      => $participant && $participant->profile_photo_path
                        ? asset('storage/' . $participant->profile_photo_path)
                        : null,
                    'last_message'=> $lastMessage?->message ?? '',
                    'time'        => $lastMessage
                        ? $lastMessage->created_at->diffForHumans()
                        : '',
                ];
            });

        return response()->json($orders);
    }
}
