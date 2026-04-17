<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('CREATE EXTENSION IF NOT EXISTS vector;');

        Schema::table('inventory_items', function (Blueprint $table) {
            $table->text('vector_string')->nullable()->after('metadata');
            // Adding a vector column with unspecified dimension to be flexible across different models.
            $table->addColumn('vector', 'embedding')->nullable()->after('vector_string');
        });

        // Add HNSW index to optimize queries
        // Because the dimension is undefined, we need to create the index thoughtfully, or we skip index creation until a fixed dimension is defined.
        // It's safest to leave out the HNSW index here if the dimension is variable.
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_items', function (Blueprint $table) {
            $table->dropColumn('vector_string');
            $table->dropColumn('embedding');
        });
    }
};
