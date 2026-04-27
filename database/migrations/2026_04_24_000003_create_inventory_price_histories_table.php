<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_price_histories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('inventory_item_id')->index();
            $table->decimal('old_price', 12, 2)->nullable();
            $table->decimal('new_price', 12, 2);
            $table->foreignId('changed_by')->nullable();
            $table->string('source')->default('manual'); // manual, import, ai_generation
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('inventory_item_id')->references('id')->on('inventory_items')->cascadeOnDelete();
            // user might be deleted, so nullify
            $table->foreign('changed_by')->references('id')->on('users')->nullOnDelete();

            $table->index(['inventory_item_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_price_histories');
    }
};
