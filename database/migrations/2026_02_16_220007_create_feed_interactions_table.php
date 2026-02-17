<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feed_interactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('feed_post_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('type'); // like, comment, share, bookmark
            $table->text('content')->nullable(); // for comments
            $table->timestamps();

            $table->index(['feed_post_id', 'type']);
            $table->index(['user_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feed_interactions');
    }
};
