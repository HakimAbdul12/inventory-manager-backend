<?php


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Dropping the constraint
        DB::statement("ALTER TABLE transfers DROP CONSTRAINT IF EXISTS transfers_status_check");
        // Re-adding with 'cancelled'
        DB::statement("ALTER TABLE transfers ADD CONSTRAINT transfers_status_check CHECK (status::text = ANY (ARRAY['pending'::text, 'accepted'::text, 'declined'::text, 'processing'::text, 'completed'::text, 'failed'::text, 'cancelled'::text]))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE transfers DROP CONSTRAINT IF EXISTS transfers_status_check");
        DB::statement("ALTER TABLE transfers ADD CONSTRAINT transfers_status_check CHECK (status::text = ANY (ARRAY['pending'::text, 'accepted'::text, 'declined'::text, 'processing'::text, 'completed'::text, 'failed'::text]))");
    }
};
