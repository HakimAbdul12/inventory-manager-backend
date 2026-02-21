<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_conversations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->string('visitor_name')->nullable();
            $table->string('visitor_email')->nullable();
            $table->string('visitor_phone')->nullable();
            $table->string('session_token', 64)->unique();
            $table->string('state')->default('ai'); // ai, human, hybrid, closed
            $table->string('telegram_chat_id')->nullable();
            $table->json('metadata')->nullable(); // language, device, referrer, ip_hash
            $table->json('ai_context')->nullable(); // conversation memory for AI
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->index(['tenant_id', 'state']);
            $table->index(['tenant_id', 'last_activity_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_conversations');
    }
};
