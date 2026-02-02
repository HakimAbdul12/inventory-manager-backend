<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('process_steps', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('process_id');
            $table->string('step_name');
            $table->integer('step_order');
            $table->string('status')->default('pending');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->json('result')->nullable();
            $table->json('logs')->nullable();
            $table->text('error')->nullable();
            $table->integer('retry_count')->default(0);
            $table->timestamps();

            $table->foreign('process_id')->references('id')->on('inventory_processes')->onDelete('cascade');
            $table->index(['process_id', 'step_order']);
            $table->index(['process_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('process_steps');
    }
};
