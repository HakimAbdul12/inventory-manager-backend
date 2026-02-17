<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->text('bio')->nullable()->after('avatar');
            $table->string('banner_image')->nullable()->after('bio');
            $table->string('location_city')->nullable()->after('banner_image');
            $table->string('location_country')->nullable()->after('location_city');
            $table->json('specialties')->nullable()->after('location_country');
            $table->unsignedInteger('years_in_business')->nullable()->after('specialties');
            $table->boolean('is_public_profile')->default(false)->after('years_in_business');
            $table->json('social_links')->nullable()->after('is_public_profile');
            $table->timestamp('last_active_at')->nullable()->after('social_links');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'bio',
                'banner_image',
                'location_city',
                'location_country',
                'specialties',
                'years_in_business',
                'is_public_profile',
                'social_links',
                'last_active_at',
            ]);
        });
    }
};
