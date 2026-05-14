<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $chatLeads = DB::table('chat_leads')->get();

        foreach ($chatLeads as $cl) {
            // Skip if already migrated (idempotent check)
            $exists = DB::table('leads')
                ->where('conversation_id', $cl->conversation_id)
                ->where('tenant_id', $cl->tenant_id)
                ->exists();

            if ($exists) {
                continue;
            }

            // Split name into first/last
            $nameParts = explode(' ', $cl->name ?? '', 2);
            $firstName = $nameParts[0] ?? null;
            $lastName = $nameParts[1] ?? null;

            // Map source type
            $sourceType = match ($cl->source ?? 'chat') {
                'chat' => 'chat',
                'email' => 'website',
                default => 'manual',
            };

            // Map recorded_by_type
            $recordedByType = match ($cl->source ?? 'chat') {
                'chat' => 'ai_agent',
                'email' => 'system',
                default => 'system',
            };

            DB::table('leads')->insert([
                'id' => Str::uuid()->toString(),
                'tenant_id' => $cl->tenant_id,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $cl->email,
                'phone' => $cl->phone,
                'source' => $cl->source ?? 'chat',
                'source_type' => $sourceType,
                'source_name' => $cl->provider_name,
                'recorded_by_type' => $recordedByType,
                'recorded_by_id' => null,
                'status' => $cl->status ?? 'new',
                'lifecycle_stage' => 'new',
                'conversation_id' => $cl->conversation_id,
                'intent' => $cl->intent,
                'interested_vehicle_id' => $cl->interested_vehicle_id,
                'vehicle_details' => $cl->vehicle_details,
                'provider_name' => $cl->provider_name,
                'external_reference_id' => $cl->external_reference_id ?? null,
                'notes' => $cl->notes,
                'created_at' => $cl->created_at ?? now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // Remove leads that were migrated from chat_leads (those with a conversation_id)
        DB::table('leads')->whereNotNull('conversation_id')->delete();
    }
};
