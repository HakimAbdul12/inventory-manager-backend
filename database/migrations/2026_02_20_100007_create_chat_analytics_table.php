<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_analytics', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->date('date');
            $table->integer('total_conversations')->default(0);
            $table->integer('total_messages')->default(0);
            $table->integer('human_handoff_count')->default(0);
            $table->integer('leads_captured')->default(0);
            $table->decimal('avg_confidence_score', 5, 2)->default(0);
            $table->json('most_requested_vehicles')->nullable();
            $table->integer('avg_response_time_seconds')->default(0);
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->unique(['tenant_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_analytics');
    }
};
