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
        Schema::table('chat_room_members', function (Blueprint $table) {
            $table->boolean('is_favorite')->default(false)->after('last_read_at');
            $table->boolean('is_pinned')->default(false)->after('is_favorite');
        });

        Schema::table('chat_rooms', function (Blueprint $table) {
            $table->dropColumn('is_favorite');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pivot', function (Blueprint $table) {
            //
        });
    }
};
