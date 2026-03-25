<?php

namespace App\Http\Controllers\Api;

use App\Events\NewHandoffRequest;
use App\Events\WidgetMessageSent;
use App\Events\WidgetStateChanged;
use App\Models\ChatConversation;
use App\Models\ChatWidgetMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class ChatConversationController extends Controller
{
    /**
     * Get ALL conversations for the tenant (full history with filtering).
     */
    public function index(Request $request): JsonResponse
    {
        $tenantId = $request->header('X-Tenant-ID');
        $stateFilter = $request->query('state'); // ai, human, closed, or null for all

        $query = ChatConversation::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->with(['messages' => fn($q) => $q->latest()->limit(1)])
            ->with('lead')
            ->orderByDesc('last_activity_at');

        if ($stateFilter) {
            $query->where('state', $stateFilter);
        }

        $conversations = $query->paginate(30);

        $conversations->getCollection()->transform(function ($conv) {
            $lastMsg = $conv->messages->first();
            return [
                'id' => $conv->id,
                'visitor_name' => $conv->visitor_name ?: 'Anonymous',
                'visitor_email' => $conv->visitor_email,
                'visitor_phone' => $conv->visitor_phone,
                'state' => $conv->state,
                'last_message' => $lastMsg?->content,
                'last_message_sender' => $lastMsg?->sender_type,
                'handoff_requested_at' => $conv->last_activity_at?->toISOString(),
                'created_at' => $conv->created_at->toISOString(),
                'message_count' => $conv->messages()->count(),
            ];
        });

        return response()->json($conversations);
    }

    /**
     * Get conversations requiring human attention (pending handoffs).
     */
    public function pendingHandoffs(Request $request): JsonResponse
    {
        $tenantId = $request->header('X-Tenant-ID');

        $conversations = ChatConversation::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->whereIn('state', [ChatConversation::STATE_HUMAN, ChatConversation::STATE_HYBRID])
            ->with(['messages' => fn($q) => $q->latest()->limit(1)])
            ->with('lead')
            ->orderByDesc('last_activity_at')
            ->get()
            ->map(function ($conv) {
                $lastMsg = $conv->messages->first();
                return [
                    'id' => $conv->id,
                    'visitor_name' => $conv->visitor_name ?: 'Anonymous',
                    'visitor_email' => $conv->visitor_email,
                    'visitor_phone' => $conv->visitor_phone,
                    'state' => $conv->state,
                    'last_message' => $lastMsg?->content,
                    'last_message_sender' => $lastMsg?->sender_type,
                    'handoff_requested_at' => $conv->last_activity_at?->toISOString(),
                    'created_at' => $conv->created_at->toISOString(),
                    'message_count' => $conv->messages()->count(),
                ];
            });

        return response()->json(['data' => $conversations]);
    }

    /**
     * Get a single conversation with all messages (for live chat panel).
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $tenantId = $request->header('X-Tenant-ID');

        $conversation = ChatConversation::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->where('id', $id)
            ->first();

        if (!$conversation) {
            return response()->json(['error' => 'Conversation not found'], 404);
        }

        $messages = $conversation->messages()
            ->orderBy('created_at')
            ->get()
            ->map(fn($m) => [
                'id' => $m->id,
                'content' => $m->content,
                'sender_type' => $m->sender_type,
                'message_type' => $m->message_type,
                'created_at' => $m->created_at->toISOString(),
            ]);

        return response()->json([
            'conversation' => [
                'id' => $conversation->id,
                'visitor_name' => $conversation->visitor_name ?: 'Anonymous',
                'visitor_email' => $conversation->visitor_email,
                'state' => $conversation->state,
                'created_at' => $conversation->created_at->toISOString(),
            ],
            'messages' => $messages,
        ]);
    }

    /**
     * Dealer replies directly from the dashboard.
     */
    public function reply(Request $request, string $id): JsonResponshttp://localhost:8000/widget/wk_PWLW0SWEBdSARyYp0TsYjTjxuayy72EiGqJ2fTAqHd4fq7R3HQdbTYTT0nQKt/message
e
    {
        $request->validate(['message' => 'required|string|max:2000']);

        $tenantId = $request->header('X-Tenant-ID');

        $conversation = ChatConversation::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->where('id', $id)
            ->first();

        if (!$conversation) {
            return response()->json(['error' => 'Conversation not found'], 404);
        }

        // Switch to human mode if not already
        if ($conversation->state === ChatConversation::STATE_AI) {
            $previousState = $conversation->state;
            $conversation->transitionTo(ChatConversation::STATE_HUMAN);
            broadcast(new WidgetStateChanged($conversation->id, $previousState, ChatConversation::STATE_HUMAN));
        }

        // Store the dealer's message
        $message = ChatWidgetMessage::create([
            'conversation_id' => $conversation->id,
            'content' => $request->message,
            'sender_type' => 'human',
            'message_type' => 'text',
            'metadata' => ['source' => 'dashboard'],
        ]);

        $conversation->touchActivity();

        // Broadcast to widget in real-time
        broadcast(new WidgetMessageSent($conversation->id, [
            'id' => $message->id,
            'content' => $message->content,
            'sender_type' => $message->sender_type,
            'message_type' => $message->message_type,
            'created_at' => $message->created_at->toISOString(),
        ]));

        return response()->json([
            'message' => [
                'id' => $message->id,
                'content' => $message->content,
                'sender_type' => $message->sender_type,
                'created_at' => $message->created_at->toISOString(),
            ]
        ]);
    }

    /**
     * End human session — hand conversation back to AI.
     */
    public function endAndHandToAI(Request $request, string $id): JsonResponse
    {
        $tenantId = $request->header('X-Tenant-ID');

        $conversation = ChatConversation::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->where('id', $id)
            ->first();

        if (!$conversation) {
            return response()->json(['error' => 'Conversation not found'], 404);
        }

        $previousState = $conversation->state;

        // Transition back to AI
        $conversation->transitionTo(ChatConversation::STATE_AI);

        // Add a system message
        $msg = $conversation->messages()->create([
            'sender_type' => ChatWidgetMessage::SENDER_AI,
            'content' => 'The support agent has ended the session. I\'m your AI assistant — how can I help you?',
            'message_type' => ChatWidgetMessage::TYPE_SYSTEM,
        ]);

        // Broadcast state change to widget
        broadcast(new WidgetStateChanged(
            $conversation->id,
            $previousState,
            ChatConversation::STATE_AI,
        ));

        // Broadcast the system message
        broadcast(new WidgetMessageSent($conversation->id, [
            'id' => $msg->id,
            'content' => $msg->content,
            'sender_type' => $msg->sender_type,
            'message_type' => $msg->message_type,
            'created_at' => $msg->created_at->toISOString(),
        ]));

        return response()->json([
            'message' => 'Conversation handed back to AI',
            'state' => 'ai',
        ]);
    }

    /**
     * Fully close a conversation (removes from all queues).
     */
    public function close(Request $request, string $id): JsonResponse
    {
        $tenantId = $request->header('X-Tenant-ID');

        $conversation = ChatConversation::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->where('id', $id)
            ->first();

        if (!$conversation) {
            return response()->json(['error' => 'Conversation not found'], 404);
        }

        $previousState = $conversation->state;
        $conversation->close();

        broadcast(new WidgetStateChanged(
            $conversation->id,
            $previousState,
            ChatConversation::STATE_CLOSED,
        ));

        return response()->json(['message' => 'Conversation closed']);
    }
}
