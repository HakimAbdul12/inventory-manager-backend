<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tenant_email_settings', function (Blueprint $table) {
            $table->string('mail_mailer')->default('smtp')->after('imap_password')->nullable();
            $table->string('mail_host')->after('mail_mailer')->nullable();
            $table->integer('mail_port')->after('mail_host')->nullable();
            $table->string('mail_username')->after('mail_port')->nullable();
            $table->text('mail_password')->after('mail_username')->nullable();
            $table->string('mail_encryption')->after('mail_password')->nullable();
            $table->string('mail_from_address')->after('mail_encryption')->nullable();
            $table->string('mail_from_name')->after('mail_from_address')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenant_email_settings', function (Blueprint $table) {
            $table->dropColumn([
                'mail_mailer',
                'mail_host',
                'mail_port',
                'mail_username',
                'mail_password',
                'mail_encryption',
                'mail_from_address',
                'mail_from_name',
            ]);
        });
    }
};
