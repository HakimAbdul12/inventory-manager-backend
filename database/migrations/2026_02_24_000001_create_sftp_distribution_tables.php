<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── SFTP Connections ──────────────────────────────────────
        Schema::create('sftp_connections', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->string('name');
            $table->string('host');
            $table->unsignedSmallInteger('port')->default(22);
            $table->string('username');
            $table->text('encrypted_password')->nullable();
            $table->text('encrypted_private_key')->nullable();
            $table->enum('auth_type', ['password', 'private_key'])->default('password');
            $table->string('default_remote_path')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_tested_at')->nullable();
            $table->enum('last_test_status', ['success', 'failed'])->nullable();
            $table->text('last_test_message')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
        });

        // ── Inventory Push Jobs ──────────────────────────────────
        Schema::create('inventory_push_jobs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->string('name');
            $table->enum('type', ['one_time', 'scheduled'])->default('one_time');
            $table->string('schedule_time')->nullable(); // "00:00" or "12:00"
            $table->enum('file_format', ['csv', 'xml', 'json'])->default('csv');
            $table->json('filters')->nullable();
            $table->json('category_ids')->nullable();
            $table->json('sftp_connection_ids');
            $table->string('destination_folder_override')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_run_at')->nullable();
            $table->timestamp('next_run_at')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
        });

        // ── Inventory Push History ───────────────────────────────
        Schema::create('inventory_push_history', function (Blueprint $table) {
            $table->id();
            $table->uuid('tenant_id')->index();
            $table->uuid('push_job_id')->nullable();
            $table->string('triggered_by'); // user UUID or "system"
            $table->enum('file_format', ['csv', 'xml', 'json']);
            $table->unsignedInteger('total_records')->default(0);
            $table->enum('status', ['pending', 'running', 'success', 'partial', 'failed'])->default('pending');
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedInteger('execution_time_ms')->nullable();
            $table->string('file_name')->nullable();
            $table->json('target_connections')->nullable();
            $table->json('connection_results')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('push_job_id')->references('id')->on('inventory_push_jobs')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_push_history');
        Schema::dropIfExists('inventory_push_jobs');
        Schema::dropIfExists('sftp_connections');
    }
};
