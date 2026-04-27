<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    /**
     * List activity logs for the current tenant (paginated, filterable).
     */
    public function index(Request $request): JsonResponse
    {
        $query = ActivityLog::with(['user:id,name,email,avatar'])
            ->orderByDesc('created_at');

        // Filter by action
        if ($request->filled('action')) {
            $query->forAction($request->action);
        }

        // Filter by action prefix (e.g., 'inventory' matches 'inventory.created', 'inventory.updated', etc.)
        if ($request->filled('action_prefix')) {
            $query->where('action', 'like', $request->action_prefix . '.%');
        }

        // Filter by user
        if ($request->filled('user_id')) {
            $query->byUser($request->user_id);
        }

        // Filter by date range
        if ($request->filled('from') || $request->filled('to')) {
            $query->inDateRange($request->from, $request->to);
        }

        // Filter by subject
        if ($request->filled('subject_type') && $request->filled('subject_id')) {
            $query->forSubjectType($request->subject_type, $request->subject_id);
        }

        $logs = $query->paginate($request->input('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $logs->map(fn (ActivityLog $log) => [
                'id' => $log->id,
                'action' => $log->action,
                'description' => $log->description,
                'properties' => $log->properties,
                'user' => $log->user ? [
                    'id' => $log->user->id,
                    'name' => $log->user->name,
                    'email' => $log->user->email,
                    'avatar' => $log->user->avatar,
                ] : null,
                'subject_type' => $log->subject_type ? class_basename($log->subject_type) : null,
                'subject_id' => $log->subject_id,
                'ip_address' => $log->ip_address,
                'created_at' => $log->created_at->toIso8601String(),
            ]),
            'pagination' => [
                'currentPage' => $logs->currentPage(),
                'lastPage' => $logs->lastPage(),
                'perPage' => $logs->perPage(),
                'total' => $logs->total(),
            ],
        ]);
    }

    /**
     * Get a single activity log entry.
     */
    public function show(string $id): JsonResponse
    {
        $log = ActivityLog::with(['user:id,name,email,avatar'])->find($id);

        if (!$log) {
            return response()->json([
                'success' => false,
                'message' => 'Activity log entry not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $log->id,
                'action' => $log->action,
                'description' => $log->description,
                'properties' => $log->properties,
                'user' => $log->user ? [
                    'id' => $log->user->id,
                    'name' => $log->user->name,
                    'email' => $log->user->email,
                    'avatar' => $log->user->avatar,
                ] : null,
                'subject_type' => $log->subject_type ? class_basename($log->subject_type) : null,
                'subject_id' => $log->subject_id,
                'ip_address' => $log->ip_address,
                'user_agent' => $log->user_agent,
                'created_at' => $log->created_at->toIso8601String(),
            ],
        ]);
    }

    /**
     * Get activity logs for a specific entity.
     */
    public function forSubject(Request $request, string $type, string $id): JsonResponse
    {
        // Map short type names to full model class names
        $typeMap = [
            'inventory' => \App\Models\InventoryItem::class,
            'InventoryItem' => \App\Models\InventoryItem::class,
        ];

        $morphType = $typeMap[$type] ?? $type;

        $logs = ActivityLog::with(['user:id,name,email,avatar'])
            ->forSubjectType($morphType, $id)
            ->orderByDesc('created_at')
            ->paginate($request->input('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $logs->map(fn (ActivityLog $log) => [
                'id' => $log->id,
                'action' => $log->action,
                'description' => $log->description,
                'properties' => $log->properties,
                'user' => $log->user ? [
                    'id' => $log->user->id,
                    'name' => $log->user->name,
                    'avatar' => $log->user->avatar,
                ] : null,
                'created_at' => $log->created_at->toIso8601String(),
            ]),
            'pagination' => [
                'currentPage' => $logs->currentPage(),
                'lastPage' => $logs->lastPage(),
                'perPage' => $logs->perPage(),
                'total' => $logs->total(),
            ],
        ]);
    }
}
