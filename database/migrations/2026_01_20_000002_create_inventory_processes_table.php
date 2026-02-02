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
        Schema::create('inventory_processes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('user_id')->index(); // User identifier
            $table->uuid('category_id');
            $table->json('user_inputs'); // User-provided field values
            $table->text('custom_prompt')->nullable();
            $table->json('options'); // Generation options (generateImages, imageCount, etc.)
            $table->string('current_step')->nullable();
            $table->string('status')->default('pending');
            $table->uuid('inventory_item_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->foreign('category_id')->references('id')->on('categories')->onDelete('cascade');
            $table->index(['user_id', 'status']);
            $table->index(['status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_processes');
    }
};
