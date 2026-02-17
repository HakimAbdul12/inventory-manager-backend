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
use Illuminate\Support\Facades\Storage;

class ChatAttachmentController extends Controller
{
    /**
     * Upload a file/image/voice attachment and create a ChatMessage for it.
     */
    public function upload(Request $request, int $roomId): JsonResponse
    {
        $user = $request->user();

        // Verify membership
        $isMember = ChatRoomMember::where('chat_room_id', $roomId)
            ->where('user_id', $user->id)->exists();

        if (!$isMember) {
            return response()->json(['message' => 'Not a member of this room.'], 403);
        }

        $validated = $request->validate([
            'file' => 'required|file|max:25600', // 25MB max
            'type' => 'required|in:image,file,voice',
            'reply_to_id' => 'nullable|integer|exists:chat_messages,id',
            'duration' => 'nullable|numeric', // voice note duration in seconds
            'body' => 'nullable|string|max:1000',
        ]);

        $file = $request->file('file');
        $type = $validated['type'];

        // Validate mime types based on type
        $mime = $file->getMimeType();

        $allowedMimes = match ($type) {
            'image' => [
                'image/jpeg',
                'image/png',
                'image/gif',
                'image/webp',
                'image/svg+xml',
                'image/bmp',
                'image/heic',
                'image/heif',
                'image/avif',
                'image/tiff',
            ],
            'voice' => [
                'audio/webm',
                'audio/ogg',
                'audio/mpeg',
                'audio/mp4',
                'audio/wav',
                'audio/x-wav',
                'audio/aac',
                'audio/flac',
                'audio/mp3',
                'audio/x-m4a',
                'video/webm',  // browsers sometimes encode voice as video/webm
                'application/octet-stream', // fallback for blobs
            ],
            'file'  => [
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'application/vnd.ms-powerpoint',
                'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                'application/zip',
                'application/x-rar-compressed',
                'application/gzip',
                'application/json',
                'text/plain',
                'text/csv',
                'text/html',
                'image/jpeg',
                'image/png',
                'image/gif',
                'image/webp',
                'image/svg+xml',
                'image/bmp',
                'application/octet-stream',
            ],
        };

        if (!in_array($mime, $allowedMimes)) {
            return response()->json([
                'message' => 'File type not allowed.',
                'detected_mime' => $mime,
                'allowed' => $allowedMimes,
            ], 422);
        }

        // Store the file
        $path = $file->store("chat-attachments/{$roomId}", 'public');
        $url = Storage::disk('public')->url($path);

        // Build metadata
        $metadata = [
            'file_url' => $url,
            'file_name' => $file->getClientOriginalName(),
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
        ];

        if ($type === 'voice' && isset($validated['duration'])) {
            $metadata['duration'] = (float) $validated['duration'];
        }

        // Generate a body preview or use provided body
        $body = $validated['body'] ?? match ($type) {
            'image' => '📷 Photo',
            'voice' => '🎤 Voice message',
            'file'  => '📎 ' . $file->getClientOriginalName(),
        };

        // Create the message
        $message = ChatMessage::create([
            'chat_room_id' => $roomId,
            'sender_id' => $user->id,
            'body' => $body,
            'type' => $type,
            'metadata' => $metadata,
            'reply_to_id' => $validated['reply_to_id'] ?? null,
        ]);

        $message->load(['sender:id,name,avatar', 'replyTo:id,body,sender_id', 'replyTo.sender:id,name']);

        // Update sender's last_read_at
        ChatRoomMember::where('chat_room_id', $roomId)
            ->where('user_id', $user->id)
            ->update(['last_read_at' => now()]);

        // Broadcast
        broadcast(new ChatMessageSent($message))->toOthers();

        // Notify members
        $room = ChatRoom::with('members')->find($roomId);
        $roomName = $room->type === 'direct' ? $user->name : $room->name;

        foreach ($room->members as $member) {
            if ($member->id !== $user->id) {
                broadcast(new NewChatNotification($message, $member->id, $roomName));
            }
        }

        return response()->json(['message' => $message], 201);
    }
}
