<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add current_tenant_id to users
        Schema::table('users', function (Blueprint $table) {
            $table->uuid('current_tenant_id')->nullable()->after('remember_token');
        });

        // Add tenant_id to inventory items
        Schema::table('inventory_items', function (Blueprint $table) {
            $table->uuid('tenant_id')->nullable()->after('id');
            $table->index('tenant_id');
        });

        // Add tenant_id to inventory processes
        Schema::table('inventory_processes', function (Blueprint $table) {
            $table->uuid('tenant_id')->nullable()->after('id');
            $table->index('tenant_id');
        });

        // Add tenant_id to imports
        Schema::table('imports', function (Blueprint $table) {
            $table->uuid('tenant_id')->nullable()->after('id');
            $table->index('tenant_id');
        });

        // Add tenant_id to transfers
        Schema::table('transfers', function (Blueprint $table) {
            $table->uuid('tenant_id')->nullable()->after('id');
            $table->index('tenant_id');
        });

        // Add tenant_id to virtual showrooms
        Schema::table('virtual_showrooms', function (Blueprint $table) {
            $table->uuid('tenant_id')->nullable()->after('id');
            $table->index('tenant_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('current_tenant_id');
        });

        $tables = ['inventory_items', 'inventory_processes', 'imports', 'transfers', 'virtual_showrooms'];
        foreach ($tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropIndex(['tenant_id']);
                $table->dropColumn('tenant_id');
            });
        }
    }
};
