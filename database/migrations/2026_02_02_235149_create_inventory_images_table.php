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
        Schema::create('inventory_images', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('inventory_item_id')->constrained()->cascadeOnDelete();
            $table->string('path'); // Path to original image
            $table->text('prompt')->nullable();
            $table->string('generated_by')->nullable();
            $table->string('alt')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->boolean('has_finished_processing')->default(false);
            $table->json('sizes')->nullable(); // Stores paths for variants (original, thumbnail, large, etc.)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_images');
    }
};
