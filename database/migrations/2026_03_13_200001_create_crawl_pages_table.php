<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crawl_pages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('crawl_job_id');
            $table->string('url', 2048);
            $table->string('normalized_url', 2048);
            $table->string('status')->default('discovered');
            $table->unsignedSmallInteger('http_status')->nullable();
            $table->unsignedSmallInteger('depth')->default(0);
            $table->string('title', 512)->nullable();
            $table->text('meta_description')->nullable();
            $table->text('clean_text')->nullable();
            $table->string('content_hash', 64)->nullable();
            $table->unsignedInteger('word_count')->default(0);
            $table->boolean('include_in_rag')->default(true);
            $table->timestamps();

            $table->foreign('crawl_job_id')->references('id')->on('crawl_jobs')->cascadeOnDelete();
            $table->index(['crawl_job_id', 'status']);
            $table->unique(['crawl_job_id', 'normalized_url']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crawl_pages');
    }
};
