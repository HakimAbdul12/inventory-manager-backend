<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workspace_chat_configs', function (Blueprint $table) {
            $table->json('external_api_config')->nullable()->after('allowed_domains');
        });
    }

    public function down(): void
    {
        Schema::table('workspace_chat_configs', function (Blueprint $table) {
            $table->dropColumn('external_api_config');
        });
    }
};
