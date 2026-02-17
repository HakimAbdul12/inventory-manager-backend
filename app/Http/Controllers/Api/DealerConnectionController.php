<?php

namespace App\Http\Controllers\Api;

use App\Events\ConnectionAccepted;
use App\Events\ConnectionRequestSent;
use App\Http\Controllers\Controller;
use App\Models\DealerConnection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DealerConnectionController extends Controller
{
    /**
     * List the user's accepted connections.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $connections = DealerConnection::where('status', 'accepted')
            ->where(fn($q) => $q->where('sender_id', $user->id)->orWhere('receiver_id', $user->id))
            ->with([
                'sender:id,name,company_name,avatar,location_city,last_active_at',
                'receiver:id,name,company_name,avatar,location_city,last_active_at'
            ])
            ->latest()
            ->paginate($request->input('per_page', 20));

        // Map to show the "other" user
        $connections->getCollection()->transform(function ($conn) use ($user) {
            $other = $conn->sender_id === $user->id ? $conn->receiver : $conn->sender;
            return [
                'id' => $conn->id,
                'dealer' => $other,
                'connection_level' => $conn->connection_level,
                'connected_at' => $conn->updated_at,
            ];
        });

        return response()->json($connections);
    }

    /**
     * Send a connection request.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'receiver_id' => 'required|integer|exists:users,id',
            'message' => 'nullable|string|max:500',
        ]);

        $senderId = $request->user()->id;
        $receiverId = $validated['receiver_id'];

        if ($senderId === $receiverId) {
            return response()->json(['message' => 'You cannot connect with yourself.'], 422);
        }

        // Check for existing connection in either direction
        $existing = DealerConnection::where(function ($q) use ($senderId, $receiverId) {
            $q->where('sender_id', $senderId)->where('receiver_id', $receiverId);
        })->orWhere(function ($q) use ($senderId, $receiverId) {
            $q->where('sender_id', $receiverId)->where('receiver_id', $senderId);
        })->first();

        if ($existing) {
            return response()->json([
                'message' => 'Connection already exists.',
                'connection' => $existing,
            ], 409);
        }

        $connection = DealerConnection::create([
            'sender_id' => $senderId,
            'receiver_id' => $receiverId,
            'message' => $validated['message'] ?? null,
            'status' => 'pending',
        ]);

        // Notify receiver in real-time
        broadcast(new ConnectionRequestSent($connection, $request->user()))->toOthers();

        return response()->json([
            'message' => 'Connection request sent.',
            'connection' => $connection,
        ], 201);
    }

    /**
     * Update connection status (accept, reject, block) or level.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'sometimes|in:accepted,rejected,blocked',
            'connection_level' => 'sometimes|in:basic,trusted_partner,preferred_supplier,strategic_partner',
        ]);

        $connection = DealerConnection::findOrFail($id);
        $user = $request->user();

        // Only the receiver can accept/reject
        if (isset($validated['status']) && in_array($validated['status'], ['accepted', 'rejected'])) {
            if ($connection->receiver_id !== $user->id) {
                return response()->json(['message' => 'Only the receiver can accept or reject.'], 403);
            }
        }

        // Either party can block
        if (isset($validated['status']) && $validated['status'] === 'blocked') {
            if ($connection->sender_id !== $user->id && $connection->receiver_id !== $user->id) {
                return response()->json(['message' => 'Unauthorized.'], 403);
            }
        }

        $connection->update($validated);

        // Notify sender when their request is accepted
        if (isset($validated['status']) && $validated['status'] === 'accepted') {
            broadcast(new ConnectionAccepted($connection, $user))->toOthers();
        }

        return response()->json([
            'message' => 'Connection updated.',
            'connection' => $connection,
        ]);
    }

    /**
     * Get pending connection requests (received).
     */
    public function pending(Request $request): JsonResponse
    {
        $pending = DealerConnection::where('receiver_id', $request->user()->id)
            ->where('status', 'pending')
            ->with('sender:id,name,company_name,avatar,location_city')
            ->latest()
            ->get();

        return response()->json(['pending' => $pending]);
    }

    /**
     * Get mutual connections between current user and another user.
     */
    public function mutual(Request $request, int $userId): JsonResponse
    {
        $mutualIds = DealerConnection::mutualConnections($request->user()->id, $userId);

        $mutualUsers = \App\Models\User::whereIn('id', $mutualIds)
            ->select('id', 'name', 'company_name', 'avatar', 'location_city')
            ->get();

        return response()->json(['mutual_connections' => $mutualUsers]);
    }
}
