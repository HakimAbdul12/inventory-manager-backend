<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\TenantUser;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class TenantController extends Controller
{
    /**
     * List all tenants the user belongs to.
     */
    public function index(Request $request): JsonResponse
    {
        $tenants = $request->user()->getTenantsForApi();

        return response()->json([
            'data' => $tenants,
            'current_tenant_id' => $request->user()->current_tenant_id,
        ]);
    }

    /**
     * Create a new tenant (workspace).
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'logo' => ['nullable', 'string', 'max:2048'],
        ]);

        $tenant = Tenant::create([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']) . '-' . Str::lower(Str::random(6)),
            'logo' => $validated['logo'] ?? null,
            'owner_id' => $request->user()->id,
            'settings' => [],
        ]);

        // Add the creator as owner
        $tenant->addMember($request->user(), TenantUser::ROLE_OWNER);

        // Switch to the new tenant
        $request->user()->switchTenant($tenant->id);

        return response()->json([
            'message' => 'Workspace created successfully.',
            'data' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'slug' => $tenant->slug,
                'logo' => $tenant->logo,
                'banner_image' => $tenant->banner_image,
                'description' => $tenant->description,
                'role' => TenantUser::ROLE_OWNER,
                'member_count' => 1,
                'owner_id' => $tenant->owner_id,
                'created_at' => $tenant->created_at,
            ],
        ], 201);
    }

    /**
     * Update tenant details.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $tenant = Tenant::findOrFail($id);

        // Only owners and admins can update
        $role = $tenant->getMemberRole($request->user());
        if (!in_array($role, Tenant::adminRoles())) {
            return response()->json(['message' => 'Insufficient permissions.'], 403);
        }

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'logo' => ['nullable', 'string', 'max:2048'],
            'banner_image' => ['nullable', 'string', 'max:2048'],
            'description' => ['nullable', 'string', 'max:5000'],
            'settings' => ['sometimes', 'array'],
        ]);

        $tenant->update($validated);
        $tenant->refresh();

        return response()->json([
            'message' => 'Workspace updated successfully.',
            'data' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'slug' => $tenant->slug,
                'logo' => $tenant->logo,
                'banner_image' => $tenant->banner_image,
                'description' => $tenant->description,
                'owner_id' => $tenant->owner_id,
                'created_at' => $tenant->created_at,
                'role' => $role,
                'member_count' => $tenant->getMemberCount(),
            ],
        ]);
    }

    /**
     * Switch the user's active tenant.
     */
    public function switchTenant(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tenant_id' => ['required', 'string'],
        ]);

        $switched = $request->user()->switchTenant($validated['tenant_id']);

        if (!$switched) {
            return response()->json([
                'message' => 'You are not a member of this workspace.',
            ], 403);
        }

        $tenant = $request->user()->fresh()->currentTenant;

        return response()->json([
            'message' => 'Switched workspace successfully.',
            'data' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'slug' => $tenant->slug,
                'logo' => $tenant->logo,
                'banner_image' => $tenant->banner_image,
                'description' => $tenant->description,
                'role' => $request->user()->roleInTenant($tenant),
                'member_count' => $tenant->getMemberCount(),
                'owner_id' => $tenant->owner_id,
                'created_at' => $tenant->created_at,
            ],
        ]);
    }

    /**
     * List members of a tenant.
     */
    public function members(Request $request, string $id): JsonResponse
    {
        $tenant = Tenant::findOrFail($id);

        // Verify user belongs to this tenant
        if (!$tenant->hasMember($request->user())) {
            return response()->json(['message' => 'Not a member of this workspace.'], 403);
        }

        $members = $tenant->users()
            ->select('users.id', 'users.name', 'users.email', 'users.avatar')
            ->get()
            ->map(fn(User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'avatar' => $user->avatar,
                'role' => $user->pivot->role,
                'joined_at' => $user->pivot->joined_at,
            ]);

        return response()->json(['data' => $members]);
    }

    /**
     * Add a member to a tenant.
     * If the user doesn't exist, register them first.
     * Only the workspace owner can add members.
     */
    public function addMember(Request $request, string $id): JsonResponse
    {
        $tenant = Tenant::findOrFail($id);

        // Only the owner can add members
        $requesterRole = $tenant->getMemberRole($request->user());
        if ($requesterRole !== TenantUser::ROLE_OWNER) {
            return response()->json(['message' => 'Only the workspace owner can add members.'], 403);
        }

        $validated = $request->validate([
            'email' => ['required', 'email'],
            'role' => ['required', Rule::in(Tenant::validRoles())],
            'name' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'min:8'],
        ]);

        // Cannot assign owner role through this endpoint
        if ($validated['role'] === TenantUser::ROLE_OWNER) {
            return response()->json(['message' => 'Cannot assign owner role.'], 422);
        }

        $user = User::where('email', $validated['email'])->first();
        $wasRegistered = false;

        if (!$user) {
            // Register the user — name and password required for new users
            if (empty($validated['name']) || empty($validated['password'])) {
                return response()->json([
                    'message' => 'User does not exist. Please provide a name and password to register them.',
                    'requires_registration' => true,
                ], 404);
            }

            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => \Illuminate\Support\Facades\Hash::make($validated['password']),
            ]);
            $user->assignRole('dealer');
            $wasRegistered = true;
        }

        if ($tenant->hasMember($user)) {
            return response()->json(['message' => 'User is already a member of this workspace.'], 422);
        }

        $tenant->addMember($user, $validated['role']);

        return response()->json([
            'message' => $wasRegistered
                ? 'User registered and added to workspace.'
                : 'Existing user added to workspace.',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'avatar' => $user->avatar,
                'role' => $validated['role'],
            ],
            'was_registered' => $wasRegistered,
        ], 201);
    }

    /**
     * Update a member's role.
     */
    public function updateMember(Request $request, string $tenantId, string $userId): JsonResponse
    {
        $tenant = Tenant::findOrFail($tenantId);

        // Only admins+ can update roles
        $requesterRole = $tenant->getMemberRole($request->user());
        if (!in_array($requesterRole, Tenant::adminRoles())) {
            return response()->json(['message' => 'Insufficient permissions.'], 403);
        }

        $validated = $request->validate([
            'role' => ['required', Rule::in(Tenant::validRoles())],
        ]);

        // Cannot change owner role, and non-owners cannot assign owner
        if ($validated['role'] === TenantUser::ROLE_OWNER && $requesterRole !== TenantUser::ROLE_OWNER) {
            return response()->json(['message' => 'Only owners can transfer ownership.'], 403);
        }

        $user = User::findOrFail($userId);

        if (!$tenant->hasMember($user)) {
            return response()->json(['message' => 'User is not a member.'], 404);
        }

        // Cannot change the owner's role (unless transferring ownership)
        $currentRole = $tenant->getMemberRole($user);
        if ($currentRole === TenantUser::ROLE_OWNER && $requesterRole !== TenantUser::ROLE_OWNER) {
            return response()->json(['message' => 'Cannot change the owner\'s role.'], 403);
        }

        $tenant->updateMemberRole($user, $validated['role']);

        return response()->json([
            'message' => 'Member role updated.',
            'data' => ['role' => $validated['role']],
        ]);
    }

    /**
     * Remove a member from a tenant.
     */
    public function removeMember(Request $request, string $tenantId, string $userId): JsonResponse
    {
        $tenant = Tenant::findOrFail($tenantId);

        // Only admins+ can remove members
        $requesterRole = $tenant->getMemberRole($request->user());
        if (!in_array($requesterRole, Tenant::adminRoles())) {
            return response()->json(['message' => 'Insufficient permissions.'], 403);
        }

        $user = User::findOrFail($userId);

        // Cannot remove the owner
        if ($tenant->getMemberRole($user) === TenantUser::ROLE_OWNER) {
            return response()->json(['message' => 'Cannot remove the workspace owner.'], 403);
        }

        // Cannot remove yourself
        if ((int) $userId === (int) $request->user()->id) {
            return response()->json(['message' => 'Cannot remove yourself. Use leave instead.'], 422);
        }

        $tenant->removeMember($user);

        return response()->json(['message' => 'Member removed.']);
    }

    /**
     * Upload a tenant banner image.
     */
    public function uploadBanner(Request $request, string $id): JsonResponse
    {
        $tenant = Tenant::findOrFail($id);

        $role = $tenant->getMemberRole($request->user());
        if (!in_array($role, Tenant::adminRoles())) {
            return response()->json(['message' => 'Insufficient permissions.'], 403);
        }

        $request->validate([
            'banner' => ['required', 'image', 'max:10240'], // 10MB
        ]);

        // Delete old banner if exists
        if ($tenant->banner_image) {
            Storage::disk('public')->delete($tenant->banner_image);
        }

        $path = $request->file('banner')->store('tenant-banners', 'public');
        $tenant->update(['banner_image' => $path]);

        return response()->json([
            'message' => 'Banner uploaded.',
            'banner_image' => $path,
        ]);
    }

    /**
     * Upload a tenant logo.
     */
    public function uploadLogo(Request $request, string $id): JsonResponse
    {
        $tenant = Tenant::findOrFail($id);

        $role = $tenant->getMemberRole($request->user());
        if (!in_array($role, Tenant::adminRoles())) {
            return response()->json(['message' => 'Insufficient permissions.'], 403);
        }

        $request->validate([
            'logo' => ['required', 'image', 'max:2048'], // 2MB
        ]);

        if ($tenant->logo) {
            Storage::disk('public')->delete($tenant->logo);
        }

        $path = $request->file('logo')->store('tenant-logos', 'public');
        $tenant->update(['logo' => $path]);

        return response()->json([
            'message' => 'Logo uploaded.',
            'logo' => $path,
        ]);
    }

    /**
     * Delete a tenant (owner only).
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $tenant = Tenant::findOrFail($id);

        if ($tenant->owner_id !== $request->user()->id) {
            return response()->json(['message' => 'Only the workspace owner can delete it.'], 403);
        }

        // Clean up storage
        if ($tenant->banner_image) {
            Storage::disk('public')->delete($tenant->banner_image);
        }
        if ($tenant->logo) {
            Storage::disk('public')->delete($tenant->logo);
        }

        // Switch user to another tenant if this was their current one
        $user = $request->user();
        if ($user->current_tenant_id === $tenant->id) {
            $otherTenant = $user->tenants()->where('tenants.id', '!=', $tenant->id)->first();
            $user->update(['current_tenant_id' => $otherTenant?->id]);
        }

        $tenant->delete();

        return response()->json(['message' => 'Workspace deleted.']);
    }
}
