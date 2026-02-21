<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_widget_messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('conversation_id');
            $table->string('sender_type'); // visitor, ai, human_agent
            $table->text('content');
            $table->string('message_type')->default('text'); // text, vehicle_card, cta_button, lead_form, system
            $table->json('metadata')->nullable(); // vehicle data, CTA config, confidence score
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('conversation_id')->references('id')->on('chat_conversations')->cascadeOnDelete();
            $table->index(['conversation_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_widget_messages');
    }
};
