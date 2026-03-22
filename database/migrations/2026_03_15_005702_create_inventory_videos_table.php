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
        Schema::create('inventory_videos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('inventory_item_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['upload', 'link']);
            $table->string('url')->nullable();
            $table->string('path')->nullable();
            $table->string('title')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_videos');
    }
};
