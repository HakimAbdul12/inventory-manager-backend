<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('test_drives', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->string('booking_code', 6)->unique();
            $table->uuid('conversation_id')->nullable();
            $table->uuid('vehicle_id')->nullable();
            $table->string('visitor_name')->nullable();
            $table->string('visitor_email')->nullable();
            $table->string('visitor_phone')->nullable();
            $table->date('scheduled_date');
            $table->time('scheduled_time');
            $table->time('end_time');
            $table->string('status')->default('pending'); // pending, confirmed, completed, cancelled, no_show
            $table->text('notes')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancellation_reason')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('conversation_id')->references('id')->on('chat_conversations')->onDelete('set null');
            $table->index(['tenant_id', 'scheduled_date', 'status']);
            $table->index(['booking_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('test_drives');
    }
};
