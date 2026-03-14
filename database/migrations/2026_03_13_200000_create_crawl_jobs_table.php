<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crawl_jobs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->string('seed_url', 2048);
            $table->string('status')->default('scheduled');
            $table->integer('max_depth')->nullable();
            $table->integer('max_pages')->nullable();
            $table->unsignedInteger('pages_discovered')->default(0);
            $table->unsignedInteger('pages_processed')->default(0);
            $table->unsignedInteger('pages_excluded')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crawl_jobs');
    }
};
