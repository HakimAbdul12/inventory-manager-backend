<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('knowledge_chunks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('document_id');
            $table->uuid('tenant_id'); // denormalized for fast queries
            $table->text('chunk_text');
            $table->json('embedding')->nullable(); // vector embedding array
            $table->integer('chunk_index')->default(0);
            $table->integer('token_count')->default(0);
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('document_id')->references('id')->on('knowledge_documents')->cascadeOnDelete();
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->index(['tenant_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('knowledge_chunks');
    }
};
