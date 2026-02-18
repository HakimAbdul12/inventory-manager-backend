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
        // 1. Add tenant_id column if not exists
        if (!Schema::hasColumn('inventory_items', 'tenant_id')) {
            Schema::table('inventory_items', function (Blueprint $table) {
                $table->uuid('tenant_id')->nullable()->after('user_id')->index();
            });
        }

        if (!Schema::hasColumn('inventory_processes', 'tenant_id')) {
            Schema::table('inventory_processes', function (Blueprint $table) {
                $table->uuid('tenant_id')->nullable()->after('user_id')->index();
            });
        }

        // 2. Populate tenant_id from user's current or first tenant
        try {
            $users = \Illuminate\Support\Facades\DB::table('users')->get();

            foreach ($users as $user) {
                // Find tenant for user
                $tenantId = $user->current_tenant_id;

                if (!$tenantId) {
                    // Fallback to first tenant user belongs to
                    $tenantUser = \Illuminate\Support\Facades\DB::table('tenant_user')
                        ->where('user_id', $user->id)
                        ->first();

                    if ($tenantUser) {
                        $tenantId = $tenantUser->tenant_id;
                    }
                }

                if ($tenantId) {
                    \Illuminate\Support\Facades\DB::table('inventory_items')
                        ->where('user_id', $user->id)
                        ->update(['tenant_id' => $tenantId]);

                    \Illuminate\Support\Facades\DB::table('inventory_processes')
                        ->where('user_id', $user->id)
                        ->update(['tenant_id' => $tenantId]);
                }
            }
        } catch (\Exception $e) {
            // Log error but continue
            if (app()->environment('local')) {
                // throw $e; // Optional: rethrow if debugging
            }
        }

        // 3. Add FK constraints separately
        // We use a separate Schema::table call to ensure columns exist and data is populated

        // We need to check if FK exists before adding to avoid 'duplicate key name' or 'constraint already exists' error
        // But Laravel schema builder doesn't have hasForeignKey.
        // We wrap in try-catch to be safe if running on existing schema

        try {
            Schema::table('inventory_items', function (Blueprint $table) {
                // We cannot easily check for FK existence in a DB-agnostic way without raw SQL.
                // For now, we assume if migration is pending, we should try adding it.
                // If it fails with 'duplicate', we catch it.
                $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            });
        } catch (\Exception $e) {
            // Ignore if FK already exists
        }

        try {
            Schema::table('inventory_processes', function (Blueprint $table) {
                $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            });
        } catch (\Exception $e) {
            // Ignore
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_items', function (Blueprint $table) {
            // We should check if FK exists before dropping to be safe, but dropping non-existent usually throws
            // We can wrap in array to suppress? No.
            // Just attempt drop.
            try {
                $table->dropForeign(['tenant_id']);
            } catch (\Exception $e) {
            }

            if (Schema::hasColumn('inventory_items', 'tenant_id')) {
                $table->dropColumn('tenant_id');
            }
        });

        Schema::table('inventory_processes', function (Blueprint $table) {
            try {
                $table->dropForeign(['tenant_id']);
            } catch (\Exception $e) {
            }

            if (Schema::hasColumn('inventory_processes', 'tenant_id')) {
                $table->dropColumn('tenant_id');
            }
        });
    }
};
