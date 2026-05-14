<?php

namespace App\Services\Chat;

use App\Models\ChatAnalytic;
use App\Models\ChatConversation;
use App\Models\Lead;

class LeadCaptureService
{
    /**
     * Capture a lead from a conversation into the unified Lead model.
     */
    public function captureLead(
        ChatConversation $conversation,
        array $data
    ): Lead {
        $name = $data['name'] ?? $conversation->visitor_name ?? '';
        $nameParts = explode(' ', $name, 2);

        $lead = Lead::withoutGlobalScope('tenant')->updateOrCreate(
            ['conversation_id' => $conversation->id],
            [
                'tenant_id' => $conversation->tenant_id,
                'first_name' => $nameParts[0] ?? null,
                'last_name' => $nameParts[1] ?? null,
                'email' => $data['email'] ?? $conversation->visitor_email,
                'phone' => $data['phone'] ?? $conversation->visitor_phone,
                'source_type' => Lead::SOURCE_CHAT,
                'source_name' => 'AI Chat Widget',
                'source' => 'chat',
                'recorded_by_type' => Lead::RECORDED_BY_AI,
                'provider_name' => 'AI Chat Agent',
                'intent' => $data['intent'] ?? Lead::INTENT_GENERAL,
                'interested_vehicle_id' => $data['vehicle_id'] ?? null,
                'notes' => $data['notes'] ?? null,
                'last_activity_at' => now(),
            ]
        );

        // Update conversation with contact info
        $updates = [];
        if (!empty($data['name']) && empty($conversation->visitor_name)) {
            $updates['visitor_name'] = $data['name'];
        }
        if (!empty($data['email']) && empty($conversation->visitor_email)) {
            $updates['visitor_email'] = $data['email'];
        }
        if (!empty($data['phone']) && empty($conversation->visitor_phone)) {
            $updates['visitor_phone'] = $data['phone'];
        }
        if (!empty($updates)) {
            $conversation->update($updates);
        }

        // Update analytics
        ChatAnalytic::forToday($conversation->tenant_id)->incrementStat('leads_captured');

        return $lead;
    }
}
