<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_role_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->uuid('tenant_role_id')->constrained('tenant_roles')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'tenant_role_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_role_user');
    }
};
