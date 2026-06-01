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

            // CRM source tracking columns
            if (!in_array('source_type', $columns)) {
                $table->string('source_type')->nullable();
            }
            if (!in_array('source_name', $columns)) {
                $table->string('source_name')->nullable();
            }

            // Recording origin
            if (!in_array('recorded_by_type', $columns)) {
                $table->string('recorded_by_type')->nullable();
            }
            if (!in_array('recorded_by_id', $columns)) {
                $table->unsignedBigInteger('recorded_by_id')->nullable();
            }

            // Assignment — salesperson (users.id is bigint, not uuid)
            if (!in_array('assigned_to', $columns)) {
                $table->unsignedBigInteger('assigned_to')->nullable();
                $table->foreign('assigned_to')->references('id')->on('users')->nullOnDelete();
            }

            // Lifecycle stage — broader than status
            if (!in_array('lifecycle_stage', $columns)) {
                $table->string('lifecycle_stage')->default('new');
            }

            // Chat/conversation link
            if (!in_array('conversation_id', $columns)) {
                $table->uuid('conversation_id')->nullable();
            }

            // Legacy fields carried from chat_leads
            if (!in_array('name', $columns)) {
                $table->string('name')->nullable();
            }
            if (!in_array('intent', $columns)) {
                $table->string('intent')->nullable();
            }
            if (!in_array('interested_vehicle_id', $columns)) {
                $table->uuid('interested_vehicle_id')->nullable();
            }
            if (!in_array('provider_name', $columns)) {
                $table->string('provider_name')->nullable();
            }
            if (!in_array('vehicle_details', $columns)) {
                $table->text('vehicle_details')->nullable();
            }
            if (!in_array('external_reference_id', $columns)) {
                $table->string('external_reference_id')->nullable();
            }

            // Timestamps for CRM tracking
            if (!in_array('last_contacted_at', $columns)) {
                $table->timestamp('last_contacted_at')->nullable();
            }
            if (!in_array('last_activity_at', $columns)) {
                $table->timestamp('last_activity_at')->nullable();
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
