<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\Order;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('order.chat.{orderId}', function ($user, $orderId) {
    $order = Order::with('driver')->find($orderId);

    if (! $order) {
        return false;
    }

    // Admin boleh akses semua chat
    if ($user->hasRole('admin')) {
        return true;
    }

    // Pembeli
    if ($user->id === $order->user_id) {
        return true;
    }

    // Driver (jika ada)
    if ($order->driver && $user->id === $order->driver->user_id) {
        return true;
    }

    return false;
});
