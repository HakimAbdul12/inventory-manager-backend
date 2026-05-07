<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('inventory_item_id')->constrained('inventory_items')->cascadeOnDelete();
            $table->foreignUuid('lead_id')->constrained('leads')->cascadeOnDelete();
            $table->string('status')->default('draft'); // draft, pending, closed
            $table->decimal('sale_price', 10, 2)->nullable();
            $table->decimal('taxes', 10, 2)->nullable();
            $table->decimal('fees', 10, 2)->nullable();
            $table->decimal('total_amount', 10, 2)->nullable();
            $table->json('financing_details')->nullable();
            $table->json('trade_in_details')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deals');
    }
};
