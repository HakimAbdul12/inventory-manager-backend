<?php

namespace App\Services\Chat;

use App\Models\ChatAnalytic;
use App\Models\ChatConversation;
use App\Models\ChatLead;

class LeadCaptureService
{
    /**
     * Capture a lead from a conversation.
     */
    public function captureLead(
        ChatConversation $conversation,
        array $data
    ): ChatLead {
        $lead = ChatLead::withoutGlobalScope('tenant')->updateOrCreate(
            ['conversation_id' => $conversation->id],
            [
                'tenant_id' => $conversation->tenant_id,
                'name' => $data['name'] ?? $conversation->visitor_name,
                'email' => $data['email'] ?? $conversation->visitor_email,
                'phone' => $data['phone'] ?? $conversation->visitor_phone,
                'intent' => $data['intent'] ?? ChatLead::INTENT_GENERAL,
                'interested_vehicle_id' => $data['vehicle_id'] ?? null,
                'notes' => $data['notes'] ?? null,
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
