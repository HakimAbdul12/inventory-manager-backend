<?php

namespace App\Http\Controllers\Api;

use App\Events\ChatMessageSent;
use App\Events\NewChatNotification;
use App\Http\Controllers\Controller;
use App\Models\ChatMessage;
use App\Models\ChatRoom;
use App\Models\ChatRoomMember;
use App\Models\TenantRole;
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
            ->with(['members' => function($q) {
                $q->select('users.id', 'name', 'avatar');
            }])
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
                        'roles' => [], // injected after with tenant roles
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
                    'is_favorite' => (bool) $membership->is_favorite,
                    'is_pinned' => (bool) $membership->is_pinned,
                    'created_by' => $room->created_by,
                ];
            });

        $tenant = $user->currentTenant;

        // Batch-load tenant roles for ALL tenant members in a single query
        $tenantRolesByUser = [];
        if ($tenant) {
            $rows = \DB::table('tenant_user_roles')
                ->join('tenant_roles', 'tenant_roles.id', '=', 'tenant_user_roles.tenant_role_id')
                ->where('tenant_user_roles.tenant_id', $tenant->id)
                ->select('tenant_user_roles.user_id', 'tenant_roles.id', 'tenant_roles.name', 'tenant_roles.slug')
                ->get();
            foreach ($rows as $row) {
                $tenantRolesByUser[$row->user_id][] = [
                    'id'   => $row->id,
                    'name' => $row->name,
                    'slug' => $row->slug,
                ];
            }
        }

        // Inject correct tenant roles into existing direct rooms
        $rooms = $rooms->map(function ($room) use ($tenantRolesByUser) {
            if ($room['other_user']) {
                $userId = $room['other_user']['id'];
                $room['other_user']['roles'] = $tenantRolesByUser[$userId] ?? [];
            }
            return $room;
        });

        // Build virtual rooms for tenant members without an existing direct chat
        if ($tenant) {
            $otherUsers = $tenant->users()->where('users.id', '!=', $user->id)->get();
        } else {
            $otherUsers = collect([]);
        }

        $existingDirectUsers = $rooms->filter(fn($r) => $r['type'] === 'direct' && $r['other_user'])
            ->pluck('other_user.id')->toArray();

        $virtualRooms = $otherUsers->reject(fn($u) => in_array($u->id, $existingDirectUsers))
            ->map(function ($otherUser) use ($tenantRolesByUser) {
                return [
                    'id'           => -$otherUser->id,
                    'name'         => $otherUser->name,
                    'type'         => 'direct',
                    'avatar'       => $otherUser->avatar,
                    'other_user'   => [
                        'id'     => $otherUser->id,
                        'name'   => $otherUser->name,
                        'avatar' => $otherUser->avatar,
                        'roles'  => $tenantRolesByUser[$otherUser->id] ?? [],
                    ],
                    'members_count' => 2,
                    'last_message'  => null,
                    'unread_count'  => 0,
                    'updated_at'    => $otherUser->created_at->toIso8601String(),
                    'is_favorite'   => false,
                    'is_pinned'     => false,
                ];
            });

        $allRooms = $rooms->concat($virtualRooms)->sortByDesc('updated_at')->values();

        return response()->json(['rooms' => $allRooms]);
    }

    /**
     * Toggle favorite status for a chat room.
     */
    public function toggleFavorite(Request $request, int $roomId): JsonResponse
    {
        $user = $request->user();

        // Verify membership
        $membership = ChatRoomMember::where('chat_room_id', $roomId)
            ->where('user_id', $user->id)->first();

        if (!$membership) {
            return response()->json(['message' => 'Not a member of this room.'], 403);
        }

        $membership->update(['is_favorite' => !$membership->is_favorite]);

        return response()->json([
            'message' => $membership->is_favorite ? 'Chat marked as favorite.' : 'Chat removed from favorites.',
            'is_favorite' => $membership->is_favorite,
        ]);
    }

    /**
     * Toggle pinned status for a chat room.
     */
    public function togglePinRoom(Request $request, int $roomId): JsonResponse
    {
        $user = $request->user();

        $membership = ChatRoomMember::where('chat_room_id', $roomId)
            ->where('user_id', $user->id)->first();

        if (!$membership) {
            return response()->json(['message' => 'Not a member of this room.'], 403);
        }

        $membership->update(['is_pinned' => !$membership->is_pinned]);

        return response()->json([
            'message' => $membership->is_pinned ? 'Chat pinned.' : 'Chat unpinned.',
            'is_pinned' => $membership->is_pinned,
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
            $tenant = $user->currentTenant;
            $otherUser = $tenant ? $tenant->users()->where('users.id', $otherUserId)->first() : null;

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
     * Add a member to a group chat.
     */
    public function addMember(Request $request, int $roomId): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
        ]);

        $user = $request->user();

        // Verify membership of the current user
        $isMember = ChatRoomMember::where('chat_room_id', $roomId)
            ->where('user_id', $user->id)->exists();

        if (!$isMember) {
            return response()->json(['message' => 'Not a member of this room.'], 403);
        }

        $room = ChatRoom::findOrFail($roomId);
        if ($room->type !== 'group') {
            return response()->json(['message' => 'Cannot add members to a direct chat.'], 400);
        }

        $userIdToAdd = $validated['user_id'];
        
        // Ensure user is in the tenant
        $tenant = $user->currentTenant;
        $userToAdd = $tenant ? $tenant->users()->where('users.id', $userIdToAdd)->first() : null;
        
        if (!$userToAdd) {
            return response()->json(['message' => 'User not found in your organization.'], 404);
        }

        $existing = ChatRoomMember::where('chat_room_id', $roomId)
            ->where('user_id', $userIdToAdd)->exists();

        if ($existing) {
            return response()->json(['message' => 'User is already a member.'], 400);
        }

        $room->members()->attach($userIdToAdd, [
            'role' => 'member',
            'joined_at' => now(),
        ]);

        return response()->json(['message' => 'Member added successfully.']);
    }

    /**
     * Remove a member from a group chat.
     */
    public function removeMember(Request $request, int $roomId, int $userId): JsonResponse
    {
        $user = $request->user();

        $room = ChatRoom::findOrFail($roomId);
        if ($room->type !== 'group') {
            return response()->json(['message' => 'Cannot remove members from a direct chat.'], 400);
        }

        $isMember = ChatRoomMember::where('chat_room_id', $roomId)
            ->where('user_id', $user->id)->exists();

        if (!$isMember) {
            return response()->json(['message' => 'Not a member of this room.'], 403);
        }

        if ($userId === $room->created_by && $userId !== $user->id) {
            return response()->json(['message' => 'Cannot remove the room creator.'], 403);
        }

        ChatRoomMember::where('chat_room_id', $roomId)->where('user_id', $userId)->delete();

        return response()->json(['message' => 'Member removed successfully.']);
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
