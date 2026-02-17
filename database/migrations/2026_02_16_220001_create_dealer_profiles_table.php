<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dealer_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade')->unique();
            $table->json('service_area')->nullable();
            $table->json('certifications')->nullable();
            $table->boolean('accepting_partnerships')->default(true);
            $table->boolean('is_bulk_trader')->default(false);
            $table->boolean('is_verified')->default(false);
            $table->json('verification_badges')->nullable();
            $table->decimal('reputation_score', 5, 2)->default(0);
            $table->decimal('response_time_hours', 8, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dealer_profiles');
    }
};
