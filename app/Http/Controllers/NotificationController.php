<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index()
    {
        $notifications = Auth::user()->notifications()->simplePaginate(10);
        return response()->json($notifications);
    }

    public function markAsRead($id)
    {
        $notification = Auth::user()->notifications()->findOrFail($id);
        $notification->update(['is_read' => true]);

        return response()->json(['message' => 'Notification marked as read', 'notification' => $notification]);
    }

    public function markAllRead()
    {
        Auth::user()->notifications()->update(['is_read' => true]);

        return response()->json(['message' => 'All notifications marked as read']);
    }

    public function deleteAll()
    {
        Auth::user()->notifications()->delete();

        return response()->json(['message' => 'All notifications deleted successfully']);
    }
}
