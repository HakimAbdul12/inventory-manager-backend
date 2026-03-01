<?php

use Illuminate\Database\Migrations\Migration;



use App\Models\TelegramConnection;
use App\Models\TelegramAgent;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $connections = TelegramConnection::whereNotNull('telegram_chat_id')->get();

        foreach ($connections as $conn) {
            // Check if agent already exists to avoid duplicates
            $exists = TelegramAgent::where('tenant_id', $conn->tenant_id)
                ->where('telegram_chat_id', $conn->telegram_chat_id)
                ->exists();

            if (!$exists) {
                TelegramAgent::create([
                    'tenant_id' => $conn->tenant_id,
                    'telegram_chat_id' => $conn->telegram_chat_id,
                    'first_name' => $conn->first_name ?? 'Legacy Agent',
                    'username' => $conn->telegram_username,
                    'is_active' => $conn->is_active,
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No safe way to reverse this without potentially deleting intentionally added agents
    }
};
