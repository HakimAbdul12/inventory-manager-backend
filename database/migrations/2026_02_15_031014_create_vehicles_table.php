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
        Schema::create('vehicles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('inventory_item_id')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->string('vin')->unique();
            $table->string('make');
            $table->string('model');
            $table->integer('year');
            $table->string('trim')->nullable();
            $table->integer('mileage')->nullable();
            $table->decimal('price', 12, 2)->nullable();
            $table->decimal('market_average', 12, 2)->nullable();
            $table->decimal('velocity_score', 5, 2)->default(0);
            $table->decimal('carrying_cost', 10, 2)->default(0);
            $table->integer('dol')->default(0); // Days on Lot
            $table->string('status')->default('coming_soon');
            $table->timestamps();

            $table->foreign('inventory_item_id')->references('id')->on('inventory_items')->onDelete('set null');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
