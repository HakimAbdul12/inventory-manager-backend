<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $columns = Schema::getColumnListing('leads');

            // Assignment — salesperson (users.id is bigint, not uuid)
            if (!in_array('assigned_to', $columns)) {
                $table->unsignedBigInteger('assigned_to')->nullable()->after('recorded_by_id');
                $table->foreign('assigned_to')->references('id')->on('users')->nullOnDelete();
            }

            // Lifecycle stage — broader than status
            if (!in_array('lifecycle_stage', $columns)) {
                $table->string('lifecycle_stage')->default('new')->after('status');
            }

            // Timestamps for CRM tracking
            if (!in_array('last_contacted_at', $columns)) {
                $table->timestamp('last_contacted_at')->nullable()->after('notes');
            }
            if (!in_array('last_activity_at', $columns)) {
                $table->timestamp('last_activity_at')->nullable()->after('last_contacted_at');
            }
        });

        // Add indexes (wrapped in try/catch for idempotency)
        $indexes = [
            'leads_tenant_status_idx' => ['tenant_id', 'status'],
            'leads_tenant_source_idx' => ['tenant_id', 'source_type'],
            'leads_tenant_assigned_idx' => ['tenant_id', 'assigned_to'],
            'leads_tenant_lifecycle_idx' => ['tenant_id', 'lifecycle_stage'],
            'leads_tenant_activity_idx' => ['tenant_id', 'last_activity_at'],
        ];

        foreach ($indexes as $name => $cols) {
            try {
                Schema::table('leads', function (Blueprint $table) use ($cols, $name) {
                    $table->index($cols, $name);
                });
            } catch (\Throwable $e) {
                // Index may already exist
            }
        }
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $columns = Schema::getColumnListing('leads');

            try { $table->dropIndex('leads_tenant_status_idx'); } catch (\Throwable $e) {}
            try { $table->dropIndex('leads_tenant_source_idx'); } catch (\Throwable $e) {}
            try { $table->dropIndex('leads_tenant_assigned_idx'); } catch (\Throwable $e) {}
            try { $table->dropIndex('leads_tenant_lifecycle_idx'); } catch (\Throwable $e) {}
            try { $table->dropIndex('leads_tenant_activity_idx'); } catch (\Throwable $e) {}

            try { $table->dropForeign(['assigned_to']); } catch (\Throwable $e) {}

            $toDrop = array_intersect(['assigned_to', 'lifecycle_stage', 'last_contacted_at', 'last_activity_at'], $columns);
            if (!empty($toDrop)) {
                $table->dropColumn($toDrop);
            }
        });
    }
};
