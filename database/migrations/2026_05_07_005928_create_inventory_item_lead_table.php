<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_item_lead', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('inventory_item_id')->constrained('inventory_items')->cascadeOnDelete();
            $table->foreignUuid('lead_id')->constrained('leads')->cascadeOnDelete();
            $table->timestamps();
            
            $table->unique(['inventory_item_id', 'lead_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_item_lead');
    }
};
