<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('test_drive_configs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->integer('max_per_day')->nullable(); // null = unlimited
            $table->time('start_time')->default('09:00');
            $table->time('end_time')->default('17:00');
            $table->integer('duration_minutes')->default(15);
            $table->integer('buffer_minutes')->default(0);
            $table->integer('max_concurrent')->default(1);
            $table->json('available_days')->nullable(); // [1,2,3,4,5] = Mon-Fri
            $table->json('blocked_dates')->nullable(); // ["2026-04-01"]
            $table->boolean('is_active')->default(false);
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->unique('tenant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('test_drive_configs');
    }
};
