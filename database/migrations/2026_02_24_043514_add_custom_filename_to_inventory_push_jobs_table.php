<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_push_jobs', function (Blueprint $table) {
            $table->string('custom_filename')->nullable()->after('destination_folder_override');
        });
    }

    public function down(): void
    {
        Schema::table('inventory_push_jobs', function (Blueprint $table) {
            $table->dropColumn('custom_filename');
        });
    }
};
