<?php

namespace App\Http\Controllers\Api;

use App\Events\ChatMessageSent;
use App\Events\NewChatNotification;
use App\Http\Controllers\Controller;
use App\Models\ChatMessage;
use App\Models\ChatRoom;
use App\Models\ChatRoomMember;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InAppChatController extends Controller
{
    /**
     * List the user's chat rooms with latest message and unread count.
     */
    public function rooms(Request $request): JsonResponse
    {
        $user = $request->user();

        $rooms = $user->chatRooms()
            ->with(['members:id,name,avatar'])
            ->get()
            ->map(function ($room) use ($user) {
                $lastMessage = $room->messages()->with('sender:id,name')->latest()->first();
                $membership = $room->pivot;

                $unreadCount = 0;
                if ($membership->last_read_at) {
                    $unreadCount = $room->messages()
                        ->where('created_at', '>', $membership->last_read_at)
                        ->where('sender_id', '!=', $user->id)
                        ->count();
                } else {
                    $unreadCount = $room->messages()
                        ->where('sender_id', '!=', $user->id)
                        ->count();
                }

                // For direct chats, get the other user's info
                $otherUser = null;
                if ($room->type === 'direct') {
                    $otherUser = $room->members->firstWhere('id', '!=', $user->id);
                }

                return [
                    'id' => $room->id,
                    'name' => $room->type === 'direct' && $otherUser
                        ? $otherUser->name
                        : $room->name,
                    'type' => $room->type,
                    'avatar' => $room->type === 'direct' && $otherUser
                        ? $otherUser->avatar
                        : $room->avatar,
                    'other_user' => $otherUser ? [
                        'id' => $otherUser->id,
                        'name' => $otherUser->name,
                        'avatar' => $otherUser->avatar,
                    ] : null,
                    'members_count' => $room->members->count(),
                    'last_message' => $lastMessage ? [
                        'body' => $lastMessage->body,
                        'sender_name' => $lastMessage->sender->name,
                        'sent_at' => $lastMessage->created_at->toIso8601String(),
                    ] : null,
                    'unread_count' => $unreadCount,
                    'updated_at' => $lastMessage
                        ? $lastMessage->created_at->toIso8601String()
                        : $room->created_at->toIso8601String(),
                    'is_favorite' => $room->is_favorite,
                ];
            })
            ->sortByDesc('updated_at')
            ->values();

        return response()->json(['rooms' => $rooms]);
    }

    /**
     * Toggle favorite status for a chat room.
     */
    public function toggleFavorite(Request $request, int $roomId): JsonResponse
    {
        $user = $request->user();

        // Verify membership
        $isMember = ChatRoomMember::where('chat_room_id', $roomId)
            ->where('user_id', $user->id)->exists();

        if (!$isMember) {
            return response()->json(['message' => 'Not a member of this room.'], 403);
        }

        $room = ChatRoom::findOrFail($roomId);
        $room->update(['is_favorite' => !$room->is_favorite]);

        return response()->json([
            'message' => $room->is_favorite ? 'Chat marked as favorite.' : 'Chat removed from favorites.',
            'is_favorite' => $room->is_favorite,
        ]);
    }

    /**
     * Create a chat room (or get existing DM).
     */
    public function createRoom(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required_without:name|integer|exists:users,id',
            'name' => 'required_without:user_id|string|max:255',
            'type' => 'sometimes|in:direct,group',
        ]);

        $user = $request->user();

        // Direct message
        if (isset($validated['user_id'])) {
            $otherUserId = $validated['user_id'];

            if ($user->id === $otherUserId) {
                return response()->json(['message' => 'Cannot chat with yourself.'], 422);
            }

            // Check if both users are in the same tenant
            $tenant = app('current_tenant');
            $otherUser = \App\Models\User::where('id', $otherUserId)
                ->where('tenant_id', $tenant->id)
                ->first();

            if (!$otherUser) {
                return response()->json(['message' => 'User not found in your organization.'], 404);
            }

            $room = ChatRoom::findOrCreateDirect($user->id, $otherUserId);

            return response()->json(['room' => $room->load('members:id,name,avatar')]);
        }

        // Group chat
        $room = ChatRoom::create([
            'name' => $validated['name'],
            'type' => 'group',
            'created_by' => $user->id,
        ]);

        $room->members()->attach($user->id, [
            'role' => 'admin',
            'joined_at' => now(),
        ]);

        return response()->json(['room' => $room->load('members:id,name,avatar')], 201);
    }

    /**
     * Get paginated messages for a chat room.
     */
    public function messages(Request $request, int $roomId): JsonResponse
    {
        $user = $request->user();

        // Verify membership
        $isMember = ChatRoomMember::where('chat_room_id', $roomId)
            ->where('user_id', $user->id)->exists();

        if (!$isMember) {
            return response()->json(['message' => 'Not a member of this room.'], 403);
        }

        $messages = ChatMessage::where('chat_room_id', $roomId)
            ->with([
                'sender:id,name,avatar',
                'replyTo:id,body,sender_id',
                'replyTo.sender:id,name',
                'reactions:id,chat_message_id,user_id,emoji' // Eager load reactions
            ])
            ->latest()
            ->paginate($request->input('per_page', 50));

        return response()->json($messages);
    }



    /**
     * Send a message to a chat room (broadcasts via Reverb).
     */
    public function sendMessage(Request $request, int $roomId): JsonResponse
    {
        $validated = $request->validate([
            'body' => 'required|string|max:5000',
            'type' => 'sometimes|in:text,image,file,voice,inventory_share,system',
            'metadata' => 'nullable|array',
            'reply_to_id' => 'nullable|integer|exists:chat_messages,id',
        ]);

        $user = $request->user();

        // Verify membership
        $isMember = ChatRoomMember::where('chat_room_id', $roomId)
            ->where('user_id', $user->id)->exists();

        if (!$isMember) {
            return response()->json(['message' => 'Not a member of this room.'], 403);
        }

        // Extract link previews if message contains URLs
        $metadata = $validated['metadata'] ?? [];
        $linkPreviews = $this->extractLinkPreviews($validated['body']);
        if (!empty($linkPreviews)) {
            $metadata['link_previews'] = $linkPreviews;
        }

        $message = ChatMessage::create([
            'chat_room_id' => $roomId,
            'sender_id' => $user->id,
            'body' => $validated['body'],
            'type' => $validated['type'] ?? 'text',
            'metadata' => $metadata,
            'reply_to_id' => $validated['reply_to_id'] ?? null,
        ]);

        $message->load(['sender:id,name,avatar', 'replyTo:id,body,sender_id', 'replyTo.sender:id,name']);

        // Update sender's last_read_at
        ChatRoomMember::where('chat_room_id', $roomId)
            ->where('user_id', $user->id)
            ->update(['last_read_at' => now()]);

        // Broadcast to the chat room channel
        broadcast(new ChatMessageSent($message))->toOthers();

        // Notify each room member (except sender) via their personal notification channel
        $room = ChatRoom::with('members')->find($roomId);
        $roomName = $room->type === 'direct'
            ? $user->name
            : $room->name;

        foreach ($room->members as $member) {
            if ($member->id !== $user->id) {
                broadcast(new NewChatNotification($message, $member->id, $roomName));
            }
        }

        return response()->json(['message' => $message], 201);
    }

    /**
     * Mark room as read.
     */
    public function markRead(Request $request, int $roomId): JsonResponse
    {
        ChatRoomMember::where('chat_room_id', $roomId)
            ->where('user_id', $request->user()->id)
            ->update(['last_read_at' => now()]);

        return response()->json(['message' => 'Marked as read.']);
    }

    /**
     * Get members of a chat room with full profile info.
     */
    public function roomMembers(Request $request, int $roomId): JsonResponse
    {
        $user = $request->user();

        $isMember = ChatRoomMember::where('chat_room_id', $roomId)
            ->where('user_id', $user->id)->exists();

        if (!$isMember) {
            return response()->json(['message' => 'Not a member of this room.'], 403);
        }

        $room = ChatRoom::with(['members' => function ($q) {
            $q->select(
                'users.id',
                'name',
                'email',
                'avatar',
                'company_name',
                'bio',
                'phone',
                'location_city',
                'location_country',
                'specialties',
                'years_in_business',
                'banner_image',
                'last_active_at'
            );
        }])->findOrFail($roomId);

        return response()->json(['members' => $room->members]);
    }

    /**
     * Pin/unpin a message.
     */
    public function togglePin(Request $request, int $roomId, int $messageId): JsonResponse
    {
        $user = $request->user();

        $isMember = ChatRoomMember::where('chat_room_id', $roomId)
            ->where('user_id', $user->id)->exists();

        if (!$isMember) {
            return response()->json(['message' => 'Not a member of this room.'], 403);
        }

        $message = ChatMessage::where('chat_room_id', $roomId)->findOrFail($messageId);
        $message->update(['is_pinned' => !$message->is_pinned]);

        return response()->json([
            'message' => $message->is_pinned ? 'Message pinned.' : 'Message unpinned.',
            'is_pinned' => $message->is_pinned,
        ]);
    }

    /**
     * Toggle an emoji reaction on a message.
     */
    public function toggleReaction(Request $request, int $roomId, int $messageId): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validate([
            'emoji' => 'required|string|max:10', // generic max for emoji
        ]);

        $isMember = ChatRoomMember::where('chat_room_id', $roomId)
            ->where('user_id', $user->id)->exists();

        if (!$isMember) {
            return response()->json(['message' => 'Not a member of this room.'], 403);
        }

        $message = ChatMessage::where('chat_room_id', $roomId)->findOrFail($messageId);

        // Toggle: if exists, delete; else create
        $reaction = $message->reactions()
            ->where('user_id', $user->id)
            ->where('emoji', $validated['emoji'])
            ->first();

        if ($reaction) {
            $reaction->delete();
            $action = 'removed';
        } else {
            $message->reactions()->create([
                'user_id' => $user->id,
                'emoji' => $validated['emoji'],
            ]);
            $action = 'added';
        }

        $reactions = $message->reactions()->get(['id', 'chat_message_id', 'user_id', 'emoji']);

        // Broadcast the update
        broadcast(new \App\Events\MessageReactionUpdated(
            $message->id,
            $roomId,
            $reactions->toArray()
        ))->toOthers();

        return response()->json([
            'message' => "Reaction $action.",
            'action' => $action,
            'reactions' => $reactions,
        ]);
    }

    /**
     * Extract link previews from message text.
     */
    private function extractLinkPreviews(string $text): array
    {
        $previews = [];
        $urlPattern = '/https?:\/\/[^\s]+/i';

        if (preg_match_all($urlPattern, $text, $matches)) {
            foreach ($matches[0] as $url) {
                // Basic URL validation
                if (filter_var($url, FILTER_VALIDATE_URL)) {
                    // For now, create a basic preview. In production, you'd fetch actual metadata
                    $previews[] = [
                        'url' => $url,
                        'title' => $this->getUrlTitle($url),
                        'description' => null,
                        'image' => null,
                    ];
                }
            }
        }

        return $previews;
    }

    /**
     * Get a basic title for a URL (simplified implementation).
     */
    private function getUrlTitle(string $url): string
    {
        // Extract domain from URL
        $parsed = parse_url($url);
        $domain = $parsed['host'] ?? $url;

        // Remove www. prefix
        $domain = preg_replace('/^www\./', '', $domain);

        return ucfirst($domain);
    }
}
