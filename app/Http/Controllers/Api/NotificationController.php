<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\NotificationRecipient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        $query = Notification::query()
            ->join('notification_recipients', 'notifications.id', '=', 'notification_recipients.notification_id')
            ->where('notification_recipients.user_id', $user->id)
            ->select('notifications.*', 'notification_recipients.read_at')
            ->with('sender');

        // Apply tenant isolation
        if (!$user->is_super_admin) {
            $tenantId = app()->bound('current_tenant') ? app('current_tenant')->id : $user->current_tenant_id;
            $query->where(function ($q) use ($tenantId) {
                $q->where('notifications.tenant_id', $tenantId)
                  ->orWhereNull('notifications.tenant_id');
            });
        }

        // Fuzzy search
        if ($request->filled('q')) {
            $searchTerm = $request->get('q');
            $query->where(function ($q) use ($searchTerm) {
                $q->where('notifications.title', 'like', '%' . $searchTerm . '%')
                  ->orWhere('notifications.body', 'like', '%' . $searchTerm . '%')
                  ->orWhere('notifications.category', 'like', '%' . $searchTerm . '%')
                  ->orWhere('notifications.data', 'like', '%' . $searchTerm . '%')
                  ->orWhereExists(function ($subQuery) use ($searchTerm) {
                      $subQuery->select(DB::raw(1))
                          ->from('users')
                          ->whereColumn('users.id', 'notifications.sender_id')
                          ->where('users.name', 'like', '%' . $searchTerm . '%');
                  });
            });
        }

        $notifications = $query->latest('notifications.created_at')
            ->paginate($request->get('per_page', 15));

        return response()->json($notifications);
    }

    public function unreadCount(Request $request)
    {
        $user = Auth::user();

        $query = Notification::query()
            ->join('notification_recipients', 'notifications.id', '=', 'notification_recipients.notification_id')
            ->where('notification_recipients.user_id', $user->id)
            ->whereNull('notification_recipients.read_at');

        // Apply tenant isolation
        if (!$user->is_super_admin) {
            $tenantId = app()->bound('current_tenant') ? app('current_tenant')->id : $user->current_tenant_id;
            $query->where(function ($q) use ($tenantId) {
                $q->where('notifications.tenant_id', $tenantId)
                  ->orWhereNull('notifications.tenant_id');
            });
        }

        $count = $query->count();

        return response()->json(['count' => $count]);
    }

    public function markAsRead(Request $request, $id)
    {
        $user = Auth::user();

        $recipient = NotificationRecipient::where('user_id', $user->id)
            ->where('notification_id', $id)
            ->firstOrFail();

        if (!$recipient->read_at) {
            $recipient->update(['read_at' => now()]);
        }

        $notification = Notification::with('sender')->findOrFail($id);
        // Attach user-specific read_at property
        $notification->read_at = $recipient->read_at;

        return response()->json([
            'message' => 'Marked as read',
            'data' => $notification
        ]);
    }

    public function markAllAsRead(Request $request)
    {
        $user = Auth::user();

        $query = NotificationRecipient::query()
            ->where('user_id', $user->id)
            ->whereNull('read_at');

        // Apply tenant isolation
        if (!$user->is_super_admin) {
            $tenantId = app()->bound('current_tenant') ? app('current_tenant')->id : $user->current_tenant_id;
            $query->whereIn('notification_id', function ($subQuery) use ($tenantId) {
                $subQuery->select('id')
                    ->from('notifications')
                    ->where(function ($q) use ($tenantId) {
                        $q->where('tenant_id', $tenantId)
                          ->orWhereNull('tenant_id');
                    });
            });
        }

        $query->update(['read_at' => now()]);

        return response()->json(['message' => 'All marked as read']);
    }
}
