<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\ChatLead;
use App\Models\TenantEmailSetting;
use App\Jobs\FetchEmailLeadsJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeadController extends Controller
{
    /**
     * Update the status of a lead.
     */
    public function updateStatus(Request $request, string $id): JsonResponse
    {
        $tenantId = app('current_tenant')->id;

        $validated = $request->validate([
            'status' => 'required|string|in:' . implode(',', ChatLead::STATUSES),
        ]);

        $lead = ChatLead::where('tenant_id', $tenantId)->findOrFail($id);
        $lead->status = $validated['status'];
        $lead->save();

        return response()->json([
            'message' => 'Lead status updated successfully.',
            'lead' => $lead,
        ]);
    }

    /**
     * Manually trigger fetching emails for the tenant.
     */
    public function fetchEmails(Request $request): JsonResponse
    {
        $tenantId = app('current_tenant')->id;

        $setting = TenantEmailSetting::where('tenant_id', $tenantId)->first();

        if (!$setting || !$setting->is_active || empty($setting->imap_password)) {
            return response()->json([
                'error' => 'Email settings are incomplete or inactive.'
            ], 400);
        }

        // Dispatch the job synchronously or asynchronously based on preference.
        // For manual fetch, we can dispatch it to the queue to not block the request.
        FetchEmailLeadsJob::dispatch($setting);

        return response()->json([
            'message' => 'Email sync started in the background.'
        ]);
    }
}
