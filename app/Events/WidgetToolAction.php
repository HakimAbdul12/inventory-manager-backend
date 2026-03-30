<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Broadcast when the AI starts or completes a tool action.
 * The widget shows an animated indicator (e.g., "🔍 Searching inventory...").
 */
class WidgetToolAction implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Human-readable labels for each tool.
     */
    public const TOOL_LABELS = [
        'search_inventory' => '🔍 Searching inventory...',
        'capture_lead_info' => '📋 Saving your info...',
        'get_available_test_drive_slots' => '📅 Checking availability...',
        'book_test_drive' => '🚗 Booking test drive...',
        'manage_test_drive' => '📋 Looking up booking...',
    ];

    public function __construct(
        public string $conversationId,
        public string $toolName,
        public string $status, // 'started' | 'completed'
        public ?string $displayText = null,
    ) {
        $this->displayText = $displayText ?? (self::TOOL_LABELS[$toolName] ?? '⏳ Processing...');
    }

    public function broadcastOn(): array
    {
        return [
            new Channel("chat-conversation.{$this->conversationId}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'tool.action';
    }

    public function broadcastWith(): array
    {
        return [
            'conversation_id' => $this->conversationId,
            'tool_name' => $this->toolName,
            'status' => $this->status,
            'display_text' => $this->displayText,
        ];
    }
}
