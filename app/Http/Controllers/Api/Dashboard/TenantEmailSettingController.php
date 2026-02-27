<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\TenantEmailSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TenantEmailSettingController extends Controller
{
    /**
     * Get the email settings for the current tenant.
     */
    public function show(Request $request): JsonResponse
    {
        $tenantId = app('current_tenant')->id;

        $settings = TenantEmailSetting::firstOrCreate(
            ['tenant_id' => $tenantId],
            [
                'imap_host' => '',
                'imap_port' => 993,
                'imap_username' => '',
                'imap_encryption' => 'ssl',
                'is_active' => false,
            ]
        );

        // We don't return the raw password to the frontend for security reasons, 
        // but we can return a boolean indicating if a password is set.
        $hasPassword = !empty($settings->imap_password);

        return response()->json([
            'settings' => collect($settings)->except(['imap_password'])->merge(['has_password' => $hasPassword])
        ]);
    }

    /**
     * Save/Update email settings for the current tenant.
     */
    public function update(Request $request): JsonResponse
    {
        $tenantId = app('current_tenant')->id;

        $validated = $request->validate([
            'imap_host' => 'required|string|max:255',
            'imap_port' => 'required|integer|min:1|max:65535',
            'imap_username' => 'required|string|max:255',
            'imap_password' => 'nullable|string', // Nullable if they don't want to change it
            'imap_encryption' => 'nullable|string|in:ssl,tls,false',
            'is_active' => 'boolean',
        ]);

        $settings = TenantEmailSetting::firstOrNew(['tenant_id' => $tenantId]);

        $settings->imap_host = $validated['imap_host'];
        $settings->imap_port = $validated['imap_port'];
        $settings->imap_username = $validated['imap_username'];
        $settings->imap_encryption = $validated['imap_encryption'] === 'false' ? false : $validated['imap_encryption'];

        if (isset($validated['is_active'])) {
            $settings->is_active = $validated['is_active'];
        }

        // Only update password if provided
        if (!empty($validated['imap_password'])) {
            $settings->imap_password = $validated['imap_password'];
        }

        $settings->save();

        return response()->json([
            'message' => 'Email settings saved successfully.',
            // Avoid sending boolean false as string string
            'settings' => collect($settings)->except(['imap_password'])->merge(['has_password' => !empty($settings->imap_password)])
        ]);
    }
}
