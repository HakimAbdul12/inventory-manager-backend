<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('telegram_connections', function (Blueprint $table) {
            $table->string('connection_code')->nullable()->after('telegram_chat_id');
            $table->timestamp('connection_code_expires_at')->nullable()->after('connection_code');
        });
    }

    public function down(): void
    {
        Schema::table('telegram_connections', function (Blueprint $table) {
            $table->dropColumn(['connection_code', 'connection_code_expires_at']);
        });
    }
};
