<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // ── Credit Applications Table ────────────────────────────
        if (!Schema::hasTable('credit_applications')) {
        Schema::create('credit_applications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('lead_id');
            $table->string('token', 64)->unique();
            $table->string('status')->default('not_sent'); // not_sent, sent, opened, submitted
            $table->boolean('is_active')->default(true);
            $table->jsonb('application_data')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->string('esignature_name')->nullable();
            $table->date('esignature_date')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('reactivated_by')->nullable();
            $table->timestamp('reactivated_at')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('lead_id')->references('id')->on('leads')->cascadeOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('reactivated_by')->references('id')->on('users')->nullOnDelete();

            $table->unique('lead_id'); // One credit app per lead
            $table->index(['tenant_id', 'status']);
            $table->index('token');
        });
        }

        // ── Notifications Table ──────────────────────────────────
        if (!Schema::hasTable('notifications')) {
            Schema::create('notifications', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('tenant_id')->nullable();
                $table->unsignedBigInteger('user_id'); // recipient
                $table->string('type'); // e.g. credit_application.opened, credit_application.submitted
                $table->string('title');
                $table->text('body')->nullable();
                $table->string('action_url')->nullable();
                $table->string('subject_type')->nullable(); // morph
                $table->string('subject_id')->nullable();   // morph
                $table->timestamp('read_at')->nullable();
                $table->timestamps();

                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
                $table->index(['user_id', 'read_at']);
                $table->index(['tenant_id', 'user_id']);
            });
        }

        // ── Add system template support to message_templates ─────
        Schema::table('message_templates', function (Blueprint $table) {
            $columns = Schema::getColumnListing('message_templates');

            if (!in_array('is_system', $columns)) {
                $table->boolean('is_system')->default(false)->after('is_active');
            }
            if (!in_array('system_key', $columns)) {
                $table->string('system_key')->nullable()->after('is_system');
            }
        });

        // ── Seed Credit Application Permissions ──────────────────
        $permissions = [
            ['key' => 'crm.credit_application.view',       'label' => 'View Credit Applications',       'category' => 'CRM', 'type' => 'high'],
            ['key' => 'crm.credit_application.create',     'label' => 'Create Credit Applications',     'category' => 'CRM', 'type' => 'high'],
            ['key' => 'crm.credit_application.edit',       'label' => 'Edit Credit Applications',       'category' => 'CRM', 'type' => 'high'],
            ['key' => 'crm.credit_application.send',       'label' => 'Send Credit Application Links',  'category' => 'CRM', 'type' => 'high'],
            ['key' => 'crm.credit_application.reactivate', 'label' => 'Reactivate Credit Applications', 'category' => 'CRM', 'type' => 'high'],
            ['key' => 'crm.credit_application.download',   'label' => 'Download Credit Application PDF', 'category' => 'CRM', 'type' => 'high'],
        ];

        foreach ($permissions as $perm) {
            $exists = DB::table('tenant_permissions')->where('key', $perm['key'])->exists();
            if (!$exists) {
                DB::table('tenant_permissions')->insert([
                    'id'       => \Illuminate\Support\Str::uuid()->toString(),
                    'key'      => $perm['key'],
                    'label'    => $perm['label'],
                    'category' => $perm['category'],
                    'type'     => $perm['type'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // Assign to system roles (Owner, Admin, Manager)
        $permIds = DB::table('tenant_permissions')
            ->whereIn('key', array_column($permissions, 'key'))
            ->pluck('id', 'key');

        $systemRoles = DB::table('tenant_roles')
            ->whereNull('tenant_id')
            ->where('is_system', true)
            ->get();

        foreach ($systemRoles as $role) {
            $slug = strtolower($role->slug ?? $role->name ?? '');

            // Owner, Admin, Manager get all credit app permissions
            if (in_array($slug, ['owner', 'admin', 'manager'])) {
                foreach ($permIds as $permId) {
                    $exists = DB::table('tenant_role_permissions')
                        ->where('tenant_role_id', $role->id)
                        ->where('tenant_permission_id', $permId)
                        ->exists();
                    if (!$exists) {
                        DB::table('tenant_role_permissions')->insert([
                            'tenant_role_id'       => $role->id,
                            'tenant_permission_id' => $permId,
                        ]);
                    }
                }
            }

            // Clerk gets view only
            if ($slug === 'clerk') {
                if (isset($permIds['crm.credit_application.view'])) {
                    $exists = DB::table('tenant_role_permissions')
                        ->where('tenant_role_id', $role->id)
                        ->where('tenant_permission_id', $permIds['crm.credit_application.view'])
                        ->exists();
                    if (!$exists) {
                        DB::table('tenant_role_permissions')->insert([
                            'tenant_role_id'       => $role->id,
                            'tenant_permission_id' => $permIds['crm.credit_application.view'],
                        ]);
                    }
                }
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_applications');
        Schema::dropIfExists('notifications');

        Schema::table('message_templates', function (Blueprint $table) {
            $columns = Schema::getColumnListing('message_templates');
            $toDrop = array_intersect(['is_system', 'system_key'], $columns);
            if (!empty($toDrop)) {
                $table->dropColumn($toDrop);
            }
        });

        DB::table('tenant_permissions')->whereIn('key', [
            'crm.credit_application.view',
            'crm.credit_application.create',
            'crm.credit_application.edit',
            'crm.credit_application.send',
            'crm.credit_application.reactivate',
            'crm.credit_application.download',
        ])->delete();
    }
};
