<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_communications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('lead_id');
            $table->enum('channel', ['phone', 'email', 'sms']);
            $table->enum('direction', ['outbound', 'inbound'])->default('outbound');
            $table->string('from_address')->nullable();
            $table->string('to_address');
            $table->string('subject')->nullable(); // email only
            $table->text('body')->nullable();
            $table->text('body_html')->nullable(); // email rich content
            $table->enum('status', ['draft', 'sent', 'delivered', 'failed', 'logged'])->default('logged');
            $table->json('metadata')->nullable(); // call duration, disposition, etc.
            $table->unsignedBigInteger('sent_by');
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('lead_id')->references('id')->on('leads')->cascadeOnDelete();
            $table->foreign('sent_by')->references('id')->on('users')->cascadeOnDelete();

            $table->index(['lead_id', 'channel']);
            $table->index(['tenant_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_communications');
    }
};
