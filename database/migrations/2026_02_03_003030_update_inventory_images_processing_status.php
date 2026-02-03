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
        Schema::table('inventory_images', function (Blueprint $table) {
            $table->dropColumn('has_finished_processing');
            $table->string('processing_status')->default('pending')->after('is_primary'); // pending, processing, completed, failed
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_images', function (Blueprint $table) {
            $table->dropColumn('processing_status');
            $table->boolean('has_finished_processing')->default(false);
        });
    }
};
