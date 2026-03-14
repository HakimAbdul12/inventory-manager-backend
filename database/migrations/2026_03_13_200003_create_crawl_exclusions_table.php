<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crawl_exclusions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('crawl_job_id');
            $table->string('pattern', 1024);
            $table->string('type')->default('contains');
            $table->timestamps();

            $table->foreign('crawl_job_id')->references('id')->on('crawl_jobs')->cascadeOnDelete();
            $table->index('crawl_job_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crawl_exclusions');
    }
};
