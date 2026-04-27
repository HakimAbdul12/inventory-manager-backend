<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. All available permissions in the system
        Schema::create('tenant_permissions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('key')->unique(); // e.g. 'inventory.create'
            $table->string('label'); // e.g. 'Create Inventory'
            $table->text('description')->nullable();
            $table->string('category')->index(); // e.g. 'Inventory', 'Workspace'
            $table->timestamps();
        });

        // 2. Roles, scoped to a tenant (or null for system defaults)
        Schema::create('tenant_roles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->nullable()->index(); // null means it's a global template
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->boolean('is_system')->default(false); // System roles can't be deleted
            $table->integer('level')->default(0); // Hierarchy level (higher = more power)
            $table->timestamps();

            // A tenant can't have two roles with the same slug
            $table->unique(['tenant_id', 'slug']);
        });

        // 3. Many-to-many: Roles <-> Permissions
        Schema::create('tenant_role_permissions', function (Blueprint $table) {
            $table->uuid('tenant_role_id');
            $table->uuid('tenant_permission_id');

            $table->foreign('tenant_role_id')->references('id')->on('tenant_roles')->cascadeOnDelete();
            $table->foreign('tenant_permission_id')->references('id')->on('tenant_permissions')->cascadeOnDelete();

            $table->primary(['tenant_role_id', 'tenant_permission_id']);
        });

        // 4. Many-to-many: Users <-> Roles within a Tenant
        Schema::create('tenant_user_roles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->foreignId('user_id')->index();
            $table->uuid('tenant_role_id')->index();
            $table->unsignedBigInteger('assigned_by')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('tenant_role_id')->references('id')->on('tenant_roles')->cascadeOnDelete();

            // A user can only have one specific role assignment per tenant (though maybe they have multiple roles overall)
            $table->unique(['tenant_id', 'user_id', 'tenant_role_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_user_roles');
        Schema::dropIfExists('tenant_role_permissions');
        Schema::dropIfExists('tenant_roles');
        Schema::dropIfExists('tenant_permissions');
    }
};
