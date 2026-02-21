<?php

namespace App\Http\Controllers\Api;

use App\Models\ChatAnalytic;
use App\Models\ChatConversation;
use App\Models\ChatLead;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class ChatAnalyticsController extends Controller
{
    /**
     * Get analytics summary for the current workspace.
     */
    public function summary(Request $request): JsonResponse
    {
        $request->validate([
            'from' => 'sometimes|date',
            'to' => 'sometimes|date',
        ]);

        $from = $request->get('from', now()->subDays(30)->toDateString());
        $to = $request->get('to', now()->toDateString());

        $analytics = ChatAnalytic::query()
            ->whereBetween('date', [$from, $to])
            ->orderBy('date')
            ->get();

        $totals = [
            'total_conversations' => $analytics->sum('total_conversations'),
            'total_messages' => $analytics->sum('total_messages'),
            'human_handoff_count' => $analytics->sum('human_handoff_count'),
            'leads_captured' => $analytics->sum('leads_captured'),
            'avg_confidence_score' => round($analytics->avg('avg_confidence_score'), 2),
            'avg_response_time_seconds' => round($analytics->avg('avg_response_time_seconds')),
        ];

        return response()->json([
            'totals' => $totals,
            'daily' => $analytics,
            'period' => ['from' => $from, 'to' => $to],
        ]);
    }

    /**
     * List conversations with filters.
     */
    public function conversations(Request $request): JsonResponse
    {
        $request->validate([
            'state' => 'sometimes|in:ai,human,hybrid,closed',
            'page' => 'sometimes|integer|min:1',
            'per_page' => 'sometimes|integer|min:1|max:50',
        ]);

        $query = ChatConversation::query()
            ->with(['lead'])
            ->withCount('messages')
            ->orderByDesc('last_activity_at');

        if ($request->has('state')) {
            $query->where('state', $request->state);
        }

        $conversations = $query->paginate($request->get('per_page', 20));

        return response()->json($conversations);
    }

    /**
     * View a full conversation with all messages.
     */
    public function showConversation(string $id): JsonResponse
    {
        $conversation = ChatConversation::query()
            ->with(['messages', 'lead'])
            ->findOrFail($id);

        return response()->json(['conversation' => $conversation]);
    }

    /**
     * List captured leads.
     */
    public function leads(Request $request): JsonResponse
    {
        $request->validate([
            'intent' => 'sometimes|in:' . implode(',', ChatLead::INTENTS),
            'page' => 'sometimes|integer|min:1',
            'per_page' => 'sometimes|integer|min:1|max:50',
        ]);

        $query = ChatLead::query()
            ->with(['conversation', 'interestedVehicle'])
            ->orderByDesc('created_at');

        if ($request->has('intent')) {
            $query->where('intent', $request->intent);
        }

        $leads = $query->paginate($request->get('per_page', 20));

        return response()->json($leads);
    }
}
