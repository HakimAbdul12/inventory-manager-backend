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
        Schema::create('inventory_documents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('inventory_item_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('type')->default('document'); // report, manual, etc.
            $table->string('path');
            $table->string('mime_type');
            $table->integer('size')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_documents');
    }
};
