<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workspace_chat_configs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->unique();
            $table->string('widget_api_key', 64)->unique();
            $table->string('bot_name')->default('Sales Assistant');
            $table->string('bot_personality')->default('professional'); // professional, friendly, luxury, casual
            $table->text('greeting_message')->default('Hi 👋 What\'s your name?');
            $table->json('widget_settings')->nullable(); // {primary_color, accent_color, position, auto_open_delay, logo_url, dark_mode}
            $table->json('business_hours')->nullable(); // {timezone, schedule: {mon: {open, close}}, enabled}
            $table->boolean('auto_human_handoff')->default(true);
            $table->integer('fallback_timeout_minutes')->default(5);
            $table->string('ai_aggressiveness')->default('balanced'); // informational, balanced, sales_driven
            $table->boolean('is_active')->default(false);
            $table->json('allowed_domains')->nullable(); // CORS whitelist
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workspace_chat_configs');
    }
};
