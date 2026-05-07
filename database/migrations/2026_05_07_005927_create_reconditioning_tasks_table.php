<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reconditioning_tasks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('inventory_item_id')->constrained('inventory_items')->cascadeOnDelete();
            $table->string('task_name');
            $table->string('status')->default('pending'); // pending, in_progress, completed
            $table->string('assigned_vendor')->nullable();
            $table->decimal('cost', 10, 2)->nullable();
            $table->string('priority')->default('normal'); // low, normal, high
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reconditioning_tasks');
    }
};
