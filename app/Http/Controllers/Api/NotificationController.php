<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        $notifications = Notification::where('user_id', $user->id)
            ->latest()
            ->paginate($request->get('per_page', 15));

        return response()->json($notifications);
    }

    public function unreadCount()
    {
        $user = Auth::user();

        $count = Notification::where('user_id', $user->id)
            ->whereNull('read_at')
            ->count();

        return response()->json(['count' => $count]);
    }

    public function markAsRead(Request $request, $id)
    {
        $user = Auth::user();

        $notification = Notification::where('user_id', $user->id)->findOrFail($id);
        
        if (!$notification->read_at) {
            $notification->update(['read_at' => now()]);
        }

        return response()->json(['message' => 'Marked as read', 'data' => $notification]);
    }

    public function markAllAsRead()
    {
        $user = Auth::user();

        Notification::where('user_id', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['message' => 'All marked as read']);
    }
}
