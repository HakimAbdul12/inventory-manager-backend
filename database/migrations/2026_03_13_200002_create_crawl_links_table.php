<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crawl_links', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('crawl_job_id');
            $table->uuid('from_page_id');
            $table->uuid('to_page_id');
            $table->string('anchor_text', 512)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('crawl_job_id')->references('id')->on('crawl_jobs')->cascadeOnDelete();
            $table->foreign('from_page_id')->references('id')->on('crawl_pages')->cascadeOnDelete();
            $table->foreign('to_page_id')->references('id')->on('crawl_pages')->cascadeOnDelete();
            $table->index('crawl_job_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crawl_links');
    }
};
