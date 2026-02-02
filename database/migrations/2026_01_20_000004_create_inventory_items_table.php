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
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('process_id');
            $table->string('user_id')->index();
            $table->uuid('category_id');
            $table->string('status')->default('draft');
            $table->json('generated_data'); // All generated content
            $table->json('images')->nullable(); // Image URLs and metadata
            $table->json('metadata')->nullable(); // AI model info, token counts, etc.
            $table->timestamps();

            $table->foreign('process_id')->references('id')->on('inventory_processes')->onDelete('cascade');
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('cascade');
            $table->index(['user_id', 'status']);
            $table->index(['category_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_items');
    }
};
