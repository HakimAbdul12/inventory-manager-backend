<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_records', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('inventory_item_id')->constrained('inventory_items')->cascadeOnDelete();
            $table->string('service_type'); // e.g., Oil Change, Brakes, Tires
            $table->date('date')->nullable();
            $table->decimal('cost', 10, 2)->nullable();
            $table->string('vendor')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_records');
    }
};
