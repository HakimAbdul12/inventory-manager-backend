<?php

namespace App\Http\Controllers\Api;

use App\Events\MessageSent;
use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\MessageRecipient;
use App\Models\User;
use App\Notifications\NewMessageNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MessageController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $messages = MessageRecipient::with(['message.sender', 'message.attachments'])
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json($messages);
    }

    /**
     * Display a listing of sent messages.
     */
    public function sent(Request $request)
    {
        $user = $request->user();

        $messages = Message::where('sender_id', $user->id)
            ->withCount('recipients')
            ->with('attachments')
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json($messages);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'subject' => 'required|string|max:255',
            'body' => 'required|string',
            'recipients' => 'required|array',
            'recipients.*' => 'exists:users,id',
            'recipients.*' => 'exists:users,id',
            'type' => 'required|in:direct,announcement,notification',
            'attachments.*' => 'nullable|file|max:10240', // 10MB max
        ]);

        // Assuming only admin can send messages for now, or check permissions
        // if (!$request->user()->hasRole('admin')) { abort(403); }

        $message = DB::transaction(function () use ($request) {
            $msg = Message::create([
                'sender_id' => $request->user()->id,
                'subject' => $request->input('subject'),
                'body' => $request->input('body'),
                'type' => $request->input('type'),
            ]);

            $recipients = User::whereIn('id', $request->input('recipients'))->get();

            foreach ($recipients as $recipient) {
                MessageRecipient::create([
                    'message_id' => $msg->id,
                    'user_id' => $recipient->id,
                ]);

                // Dispatch Event (for real-time)
                event(new MessageSent($msg, $recipient));

                // Send Notification (Email)
                $recipient->notify(new NewMessageNotification($msg));
            }

            // Handle Attachments
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $path = $file->store('message-attachments', 'public');

                    \App\Models\MessageAttachment::create([
                        'message_id' => $msg->id,
                        'file_path' => $path,
                        'file_name' => $file->getClientOriginalName(),
                        'file_type' => $file->getClientMimeType(),
                        'file_size' => $file->getSize(),
                    ]);
                }
            }

            return $msg;
        });

        return response()->json($message, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id, Request $request)
    {
        $recipientEndpoint = MessageRecipient::where('message_id', $id)
            ->where('user_id', $request->user()->id)
            ->where('user_id', $request->user()->id)
            ->with(['message.sender', 'message.attachments'])
            ->firstOrFail();

        if (!$recipientEndpoint->read_at) {
            $recipientEndpoint->update(['read_at' => now()]);
        }

        return response()->json($recipientEndpoint);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id, Request $request)
    {
        $recipientEndpoint = MessageRecipient::where('message_id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $recipientEndpoint->delete();

        return response()->json(['message' => 'Message deleted']);
    }

    /** 
     * Get unread count
     */
    public function unreadCount(Request $request)
    {
        $count = MessageRecipient::where('user_id', $request->user()->id)
            ->whereNull('read_at')
            ->count();

        return response()->json(['count' => $count]);
    }
}
