<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Publishing Platforms Registry (Managed by Super Admins)
        Schema::create('publishing_platforms', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('key')->unique();
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('icon_url')->nullable();
            $table->string('color')->nullable();
            $table->json('supported_types')->nullable(); // ['image', 'video']
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->json('settings')->nullable();
            $table->timestamps();
        });

        // 2. Publishing Batches (A multi-item, multi-platform publishing session)
        Schema::create('publishing_batches', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('pending'); // pending, in_progress, completed, failed
            $table->integer('total_items')->default(0);
            $table->integer('successful_items')->default(0);
            $table->integer('failed_items')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        // 3. Publishing Batch Items (Granular per-vehicle, per-platform publishing task)
        Schema::create('publishing_batch_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('batch_id')->constrained('publishing_batches')->cascadeOnDelete();
            $table->foreignUuid('inventory_item_id')->constrained('inventory_items')->cascadeOnDelete();
            $table->string('platform_key');
            $table->string('format')->default('image'); // image, video
            $table->string('status')->default('pending'); // pending, in_progress, published, failed
            $table->integer('attempts')->default(0);
            $table->integer('max_attempts')->default(3);
            $table->text('error_message')->nullable();
            $table->timestamp('last_attempted_at')->nullable();
            $table->timestamps();

            $table->index(['batch_id', 'status']);
            $table->index(['inventory_item_id', 'platform_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('publishing_batch_items');
        Schema::dropIfExists('publishing_batches');
        Schema::dropIfExists('publishing_platforms');
    }
};
