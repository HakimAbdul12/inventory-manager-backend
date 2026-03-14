<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rag_documents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('crawl_job_id');
            $table->uuid('page_id');
            $table->text('content');
            $table->unsignedInteger('chunk_index')->default(0);
            $table->json('metadata_json')->nullable();
            $table->boolean('vectorized')->default(false);
            $table->timestamps();

            $table->foreign('crawl_job_id')->references('id')->on('crawl_jobs')->cascadeOnDelete();
            $table->foreign('page_id')->references('id')->on('crawl_pages')->cascadeOnDelete();
            $table->index(['crawl_job_id', 'page_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rag_documents');
    }
};
