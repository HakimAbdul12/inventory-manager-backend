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
        Schema::table('chat_leads', function (Blueprint $table) {
            $table->string('source')->default('chat')->after('tenant_id');
            $table->string('status')->default('new')->after('intent');
            $table->string('external_reference_id')->nullable()->after('conversation_id');
            // Useful for deduplication and tracing
            $table->unique(['tenant_id', 'external_reference_id'], 'chat_leads_tenant_ext_ref_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chat_leads', function (Blueprint $table) {
            $table->dropUnique('chat_leads_tenant_ext_ref_unique');
            $table->dropColumn(['source', 'status', 'external_reference_id']);
        });
    }
};
