<?php
namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderMessage;
use App\Events\MessageSent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChatController extends Controller
{
    public function sendMessage(Request $request, $id)
    {
        $request->validate(['message' => 'required|string']);

        // Ambil data order beserta user dan driver terkait
        $order = Order::with(['user', 'driver.user'])->findOrFail($id);
        $sender = Auth::user();

        // Tentukan siapa penerima notifikasi
        $recipient = null;
        if ($sender->id === $order->user_id) {
            $recipient = $order->driver->user ?? null;
        } elseif ($sender->id === ($order->driver->user_id ?? null)) {
            $recipient = $order->user;
        }

        if (!$recipient) {
            return response()->json(['message' => 'Chat belum tersedia (Driver belum di-assign)'], 400);
        }

        // 1. Simpan Pesan
        $message = OrderMessage::create([
            'order_id' => $id,
            'sender_id' => $sender->id,
            'message' => $request->message
        ]);

        // 2. Broadcast Real-time
        broadcast(new MessageSent($message))->toOthers();

        // 3. Kirim Notifikasi (Menggunakan fungsi di User.php)
        $recipient->sendNotification(
            'Pesan baru dari ' . $sender->name,
            $request->message,
            'chat_message',
            $id
        );

        return response()->json($message->load('sender:id,name'), 201);
    }

    public function getMessages($id)
    {
        $order = Order::findOrFail($id);

        // Pastikan hanya orang yang terlibat yang bisa lihat chat
        if (Auth::id() !== $order->user_id && Auth::id() !== ($order->driver->user_id ?? null)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return OrderMessage::where('order_id', $id)
            ->with('sender:id,name')
            ->orderBy('created_at', 'asc')
            ->get();
    }
}