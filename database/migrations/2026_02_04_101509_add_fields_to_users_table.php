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
        Schema::table('users', function (Blueprint $table) {
            $table->string('dealer_code')->unique()->nullable()->after('id'); // Nullable for existing users, but new ones will generate it
            $table->string('company_name')->nullable()->after('email');
            $table->string('phone')->nullable()->after('company_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['dealer_code', 'company_name', 'phone']);
        });
    }
};
