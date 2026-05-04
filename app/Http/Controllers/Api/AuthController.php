<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\TenantUser;
use App\Models\User;
use App\Services\PermissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;

class AuthController extends Controller
{
    /**
     * Register a new user.
     */
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
            'company_name' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'company_name' => $request->company_name,
            'phone' => $request->phone,
        ]);

        $user->assignRole('dealer');

        // Create a default workspace for the user
        $workspaceName = $request->company_name ?: $user->name . "'s Workspace";
        $tenant = Tenant::create([
            'name' => $workspaceName,
            'slug' => Str::slug($workspaceName) . '-' . Str::lower(Str::random(6)),
            'owner_id' => $user->id,
            'settings' => [],
        ]);
        $tenant->addMember($user, TenantUser::ROLE_OWNER);
        $user->update(['current_tenant_id' => $tenant->id]);

        // Seed default roles and assign owner role record
        $permService = app(PermissionService::class);
        $permService->syncDefaultRoles($tenant);
        $permService->assignRoleBySlug($user, $tenant, 'owner');

        // Log the user in after registration
        Auth::login($user);

        return response()->json([
            'success' => true,
            'message' => 'Registration successful',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'dealer_code' => $user->dealer_code,
                    'company_name' => $user->company_name,
                    'role' => 'dealer',
                    'is_super_admin' => $user->is_super_admin,
                    'avatar' => $user->avatar,
                    'banner_image' => $user->banner_image,
                    // permissions are fetched separately via /permissions/me
                    'tenants' => $user->getTenantsForApi(),
                    'current_tenant' => [
                        'id' => $tenant->id,
                        'name' => $tenant->name,
                        'slug' => $tenant->slug,
                        'role' => TenantUser::ROLE_OWNER,
                    ],
                ],
            ],
        ], 201);
    }

    /**
     * Authenticate a user.
     */
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['boolean'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        if (!Auth::attempt(
            $request->only('email', 'password'),
            $request->boolean('remember')
        )) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials',
            ], 401);
        }

        $request->session()->regenerate();

        $user = Auth::user();
        $currentTenant = $user->currentTenant;

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'roles' => $user->getRoleNames(),
                    'is_super_admin' => $user->is_super_admin,
                    'avatar' => $user->avatar,
                    'banner_image' => $user->banner_image,
                    'company_name' => $user->company_name,
                    'tenants' => $user->getTenantsForApi(),
                    'current_tenant' => $currentTenant ? [
                        'id' => $currentTenant->id,
                        'name' => $currentTenant->name,
                        'slug' => $currentTenant->slug,
                        'logo' => $currentTenant->logo,
                        'role' => $user->roleInTenant($currentTenant),
                    ] : null,
                ],
            ],
        ]);
    }

    /**
     * Log out the authenticated user.
     */
    public function logout(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully',
        ]);
    }

    /**
     * Get the authenticated user.
     */
    public function user(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Not authenticated',
            ], 401);
        }

        $currentTenant = $user->currentTenant;

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'email_verified_at' => $user->email_verified_at,
                    'created_at' => $user->created_at->toIso8601String(),
                    'roles' => $user->getRoleNames(),
                    'is_super_admin' => $user->is_super_admin,
                    'avatar' => $user->avatar,
                    'dealer_code' => $user->dealer_code,
                    'company_name' => $user->company_name,
                    'phone' => $user->phone,
                    'banner_image' => $user->banner_image,
                    'tenants' => $user->getTenantsForApi(),
                    'current_tenant' => $currentTenant ? [
                        'id' => $currentTenant->id,
                        'name' => $currentTenant->name,
                        'slug' => $currentTenant->slug,
                        'logo' => $currentTenant->logo,
                        'role' => $user->roleInTenant($currentTenant),
                    ] : null,
                ],
            ],
        ]);
    }

    /**
     * Send a password reset link.
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($status === Password::RESET_LINK_SENT) {
            return response()->json([
                'success' => true,
                'message' => 'Password reset link sent',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => __($status),
        ], 400);
    }

    /**
     * Reset the password.
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->save();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json([
                'success' => true,
                'message' => 'Password has been reset',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => __($status),
        ], 400);
    }
    /**
     * Redirect to Google OAuth.
     */
    public function redirectToGoogle(): \Symfony\Component\HttpFoundation\RedirectResponse|\Illuminate\Http\RedirectResponse
    {
        return \Laravel\Socialite\Facades\Socialite::driver('google')->stateless()->redirect();
    }

    /**
     * Handle Google OAuth callback.
     */
    public function handleGoogleCallback(): \Illuminate\Http\RedirectResponse
    {
        try {
            /** @var \Laravel\Socialite\Two\User $googleUser */
            $googleUser = \Laravel\Socialite\Facades\Socialite::driver('google')->stateless()->user();
        } catch (\Exception $e) {
            return redirect(config('app.frontend_url') . '/auth/login?error=google_auth_failed');
        }

        $user = User::where('google_id', $googleUser->getId())
            ->orWhere('email', $googleUser->getEmail())
            ->first();

        if ($user) {
            // Update existing user
            if (!$user->google_id) {
                $user->update(['google_id' => $googleUser->getId()]);
            }
            if (!$user->avatar && $googleUser->getAvatar()) {
                $user->update(['avatar' => $googleUser->getAvatar()]);
            }
        } else {
            // Register new user
            $user = User::create([
                'name' => $googleUser->getName(),
                'email' => $googleUser->getEmail(),
                'google_id' => $googleUser->getId(),
                'avatar' => $googleUser->getAvatar(),
                'password' => null, // Password is null for Google users
                'email_verified_at' => now(),
            ]);

            $user->assignRole('owner');

            // Create default workspace
            $workspaceName = $user->name . "'s Workspace";
            $tenant = Tenant::create([
                'name' => $workspaceName,
                'slug' => Str::slug($workspaceName) . '-' . Str::lower(Str::random(6)),
                'owner_id' => $user->id,
                'settings' => [],
            ]);
            $tenant->addMember($user, TenantUser::ROLE_OWNER);
            $user->update(['current_tenant_id' => $tenant->id]);

            // Seed default roles and assign owner role record
            $permService = app(PermissionService::class);
            $permService->syncDefaultRoles($tenant);
            $permService->assignRoleBySlug($user, $tenant, 'owner');
        }

        Auth::login($user);
        $token = $user->createToken('auth_token')->plainTextToken;

        return redirect(config('app.frontend_url') . '/auth/google/callback?token=' . $token);
    }
}
