<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CloseInactiveChats extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'chat:close-inactive';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Close human agent chats that have been inactive for over 2 minutes and hand back to AI.';

    /**
     * Execute the console command.
     */
    public function handle(\App\Services\Chat\TelegramBotService $telegramService)
    {
        $cutoff = now()->subSeconds(120);

        $inactiveConversations = \App\Models\ChatConversation::where('state', 'human')
            ->where('last_activity_at', '<', $cutoff)
            ->get();

        $count = $inactiveConversations->count();

        foreach ($inactiveConversations as $conversation) {
            $previousState = $conversation->state;
            $agentChatId = $conversation->agent_telegram_chat_id;
            
            // Revert back to AI
            $conversation->resumeAI();

            // Notify the agent in Telegram
            if ($agentChatId) {
                $telegramService->sendMessage(
                    $agentChatId,
                    "⚠️ <b>Chat Closed Automatically</b>\nThis conversation was disconnected due to 2 minutes of inactivity."
                );
            }

            // Broadcast the state change to the frontend widget
            broadcast(new \App\Events\WidgetStateChanged(
                $conversation->id,
                $previousState,
                'ai'
            ));
        }

        $this->info("Closed {$count} inactive conversations.");
    }
}
