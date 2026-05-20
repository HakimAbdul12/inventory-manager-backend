<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\NotificationRecipient;
use App\Models\User;
use App\Models\Tenant;
use App\Models\TenantRole;
use App\DTOs\NotificationData;
use App\Events\NotificationReceived;
use App\Services\PermissionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class NotificationService
{
    protected PermissionService $permissionService;

    public function __construct(PermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }

    /**
     * Send a notification to targeted users.
     *
     * @param NotificationData $data
     * @param array $targeting Keys: user_ids, roles, permissions, global
     * @return Notification
     */
    public function send(NotificationData $data, array $targeting): Notification
    {
        $userIds = collect();

        $targetUserIds = $targeting['user_ids'] ?? [];
        $targetRoles = $targeting['roles'] ?? [];
        $targetPermissions = $targeting['permissions'] ?? [];
        $isGlobal = $targeting['global'] ?? false;

        if ($data->tenantId) {
            // Tenant-scoped notification
            $tenant = Tenant::findOrFail($data->tenantId);

            // 1. Direct user IDs (must belong to the tenant)
            if (!empty($targetUserIds)) {
                $validTenantUsers = DB::table('tenant_user')
                    ->where('tenant_id', $tenant->id)
                    ->whereIn('user_id', $targetUserIds)
                    ->pluck('user_id');
                $userIds = $userIds->merge($validTenantUsers);
            }

            // 2. Roles in this tenant
            if (!empty($targetRoles)) {
                $roleIds = TenantRole::withoutGlobalScope('tenant')
                    ->where('tenant_id', $tenant->id)
                    ->whereIn('slug', $targetRoles)
                    ->pluck('id');

                $roleUsers = DB::table('tenant_user_roles')
                    ->whereIn('tenant_role_id', $roleIds)
                    ->pluck('user_id');

                $userIds = $userIds->merge($roleUsers);
            }

            // 3. Permissions in this tenant
            if (!empty($targetPermissions)) {
                $members = $tenant->users()->get();
                foreach ($members as $member) {
                    foreach ($targetPermissions as $perm) {
                        if ($this->permissionService->userCan($perm, $member, $tenant)) {
                            $userIds->push($member->id);
                            break;
                        }
                    }
                }
            }

            // 4. Default: if no specific targeting is given, target all tenant members
            if (empty($targetUserIds) && empty($targetRoles) && empty($targetPermissions)) {
                $tenantMembers = DB::table('tenant_user')
                    ->where('tenant_id', $tenant->id)
                    ->pluck('user_id');
                $userIds = $userIds->merge($tenantMembers);
            }
        } else {
            // Global / System-level notification
            if ($isGlobal) {
                $userIds = collect(User::pluck('id'));
            } else {
                // 1. Direct user IDs
                if (!empty($targetUserIds)) {
                    $userIds = $userIds->merge($targetUserIds);
                }

                // 2. System roles (tenant_id is null)
                if (!empty($targetRoles)) {
                    $roleIds = TenantRole::withoutGlobalScope('tenant')
                        ->whereNull('tenant_id')
                        ->whereIn('slug', $targetRoles)
                        ->pluck('id');

                    $systemRoleUsers = DB::table('system_role_user')
                        ->whereIn('tenant_role_id', $roleIds)
                        ->pluck('user_id');

                    $userIds = $userIds->merge($systemRoleUsers);
                }

                // 3. System permissions (tenant is null)
                if (!empty($targetPermissions)) {
                    $allUsers = User::all();
                    foreach ($allUsers as $user) {
                        foreach ($targetPermissions as $perm) {
                            if ($this->permissionService->userCan($perm, $user, null)) {
                                $userIds->push($user->id);
                                break;
                            }
                        }
                    }
                }
            }
        }

        // Get unique, non-null user IDs
        $resolvedUserIds = $userIds->filter()->unique()->values()->toArray();

        // Create the notification record
        $notification = Notification::create($data->toArray());

        // Create recipient records
        $recipients = [];
        foreach ($resolvedUserIds as $userId) {
            $recipients[] = [
                'id' => (string) Str::uuid(),
                'notification_id' => $notification->id,
                'user_id' => $userId,
                'read_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if (!empty($recipients)) {
            NotificationRecipient::insert($recipients);

            // Trigger real-time broadcast event
            event(new NotificationReceived($notification, $resolvedUserIds));
        }

        return $notification;
    }
}
