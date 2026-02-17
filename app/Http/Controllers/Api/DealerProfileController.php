<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DealerProfile;
use App\Models\DealerConnection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DealerProfileController extends Controller
{
    /**
     * Get the authenticated dealer's own profile.
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->load('dealerProfile');

        $connectionsCount = $user->connections()->count();
        $inventoryCount = $user->inventoryItems()->count();

        return response()->json([
            'user' => $user->only([
                'id',
                'name',
                'email',
                'company_name',
                'dealer_code',
                'phone',
                'avatar',
                'bio',
                'banner_image',
                'location_city',
                'location_country',
                'specialties',
                'years_in_business',
                'is_public_profile',
                'social_links',
                'last_active_at',
                'created_at',
            ]),
            'dealer_profile' => $user->dealerProfile,
            'stats' => [
                'connections' => $connectionsCount,
                'inventory_items' => $inventoryCount,
            ],
        ]);
    }

    /**
     * View another dealer's profile.
     */
    public function viewProfile(Request $request, int $id): JsonResponse
    {
        $user = \App\Models\User::with('dealerProfile')
            ->select([
                'id',
                'name',
                'company_name',
                'dealer_code',
                'phone',
                'avatar',
                'bio',
                'banner_image',
                'location_city',
                'location_country',
                'specialties',
                'years_in_business',
                'is_public_profile',
                'social_links',
                'last_active_at',
                'created_at',
            ])
            ->findOrFail($id);

        // Check if profile is public or if they're connected
        $currentUser = $request->user();
        $isConnected = DealerConnection::areConnected($currentUser->id, $user->id);
        $isSelf = $currentUser->id === $user->id;

        if (!$user->is_public_profile && !$isConnected && !$isSelf) {
            return response()->json(['message' => 'This profile is private.'], 403);
        }

        $connectionsCount = DealerConnection::where('status', 'accepted')
            ->where(fn($q) => $q->where('sender_id', $id)->orWhere('receiver_id', $id))
            ->count();

        $mutualConnections = DealerConnection::mutualConnections($currentUser->id, $id);

        // Connection status between current user and this dealer
        $connectionStatus = null;
        $connection = DealerConnection::where(function ($q) use ($currentUser, $id) {
            $q->where('sender_id', $currentUser->id)->where('receiver_id', $id);
        })->orWhere(function ($q) use ($currentUser, $id) {
            $q->where('sender_id', $id)->where('receiver_id', $currentUser->id);
        })->first();

        if ($connection) {
            $connectionStatus = [
                'id' => $connection->id,
                'status' => $connection->status,
                'level' => $connection->connection_level,
                'initiated_by_me' => $connection->sender_id === $currentUser->id,
            ];
        }

        return response()->json([
            'user' => $user,
            'dealer_profile' => $user->dealerProfile,
            'stats' => [
                'connections' => $connectionsCount,
                'mutual_connections' => count($mutualConnections),
            ],
            'connection' => $connectionStatus,
            'is_connected' => $isConnected,
        ]);
    }

    /**
     * Update the authenticated dealer's profile.
     */
    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'bio' => 'nullable|string|max:2000',
            'location_city' => 'nullable|string|max:255',
            'location_country' => 'nullable|string|max:255',
            'specialties' => 'nullable|array',
            'specialties.*' => 'string|max:100',
            'years_in_business' => 'nullable|integer|min:0',
            'is_public_profile' => 'nullable|boolean',
            'social_links' => 'nullable|array',
            'company_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            // Dealer profile fields
            'service_area' => 'nullable|array',
            'certifications' => 'nullable|array',
            'accepting_partnerships' => 'nullable|boolean',
            'is_bulk_trader' => 'nullable|boolean',
        ]);

        $user = $request->user();

        // Update user fields
        $userFields = collect($validated)->only([
            'bio',
            'location_city',
            'location_country',
            'specialties',
            'years_in_business',
            'is_public_profile',
            'social_links',
            'company_name',
            'phone',
        ])->toArray();

        $user->update($userFields);

        // Update or create dealer profile
        $profileFields = collect($validated)->only([
            'service_area',
            'certifications',
            'accepting_partnerships',
            'is_bulk_trader',
        ])->toArray();

        if (!empty($profileFields)) {
            DealerProfile::updateOrCreate(
                ['user_id' => $user->id],
                $profileFields
            );
        }

        $user->load('dealerProfile');

        return response()->json([
            'message' => 'Profile updated successfully.',
            'user' => $user,
        ]);
    }

    /**
     * Discover dealers (search + filter).
     */
    public function discover(Request $request): JsonResponse
    {
        $query = \App\Models\User::query()
            ->where('is_public_profile', true)
            ->where('id', '!=', $request->user()->id)
            ->with('dealerProfile');

        // Search by name or company
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('company_name', 'like', "%{$search}%")
                    ->orWhere('dealer_code', 'like', "%{$search}%");
            });
        }

        // Filter by location
        if ($city = $request->input('city')) {
            $query->where('location_city', 'like', "%{$city}%");
        }
        if ($country = $request->input('country')) {
            $query->where('location_country', 'like', "%{$country}%");
        }

        // Filter by specialty
        if ($specialty = $request->input('specialty')) {
            $query->whereJsonContains('specialties', $specialty);
        }

        // Filter by verified
        if ($request->boolean('verified_only')) {
            $query->whereHas('dealerProfile', fn($q) => $q->where('is_verified', true));
        }

        // Filter by accepting partnerships
        if ($request->boolean('accepting_partnerships')) {
            $query->whereHas('dealerProfile', fn($q) => $q->where('accepting_partnerships', true));
        }

        // Filter by bulk traders
        if ($request->boolean('bulk_traders')) {
            $query->whereHas('dealerProfile', fn($q) => $q->where('is_bulk_trader', true));
        }

        // Filter by recently active (last 24 hours)
        if ($request->boolean('recently_active')) {
            $query->where('last_active_at', '>=', now()->subDay());
        }

        // Sort
        $sortBy = $request->input('sort', 'name');
        $sortDir = $request->input('sort_dir', 'asc');
        $query->orderBy($sortBy, $sortDir);

        $dealers = $query->paginate($request->input('per_page', 20));

        return response()->json($dealers);
    }

    /**
     * Get suggested connections based on common specialties and location.
     */
    public function suggestions(Request $request): JsonResponse
    {
        $user = $request->user();

        $connectedIds = DealerConnection::where('status', 'accepted')
            ->where(fn($q) => $q->where('sender_id', $user->id)->orWhere('receiver_id', $user->id))
            ->get()
            ->map(fn($c) => $c->sender_id === $user->id ? $c->receiver_id : $c->sender_id)
            ->toArray();

        // Also exclude pending requests
        $pendingIds = DealerConnection::where(fn($q) => $q->where('sender_id', $user->id)->orWhere('receiver_id', $user->id))
            ->pluck('sender_id', 'receiver_id')
            ->flatten()
            ->toArray();

        $excludeIds = array_unique(array_merge($connectedIds, $pendingIds, [$user->id]));

        $suggestions = \App\Models\User::query()
            ->where('is_public_profile', true)
            ->whereNotIn('id', $excludeIds)
            ->with('dealerProfile')
            ->limit(10)
            ->get();

        // Sort by relevance (same location first, then same specialties)
        $suggestions = $suggestions->sortByDesc(function ($dealer) use ($user) {
            $score = 0;
            if ($dealer->location_city === $user->location_city) $score += 3;
            if ($dealer->location_country === $user->location_country) $score += 1;
            $overlap = count(array_intersect($dealer->specialties ?? [], $user->specialties ?? []));
            $score += $overlap * 2;
            return $score;
        })->values();

        return response()->json(['suggestions' => $suggestions]);
    }

    /**
     * Update the authenticated user's avatar.
     */
    public function uploadAvatar(Request $request): JsonResponse
    {
        $request->validate([
            'avatar' => 'required|image|max:2048', // 2MB max
        ]);

        $user = $request->user();
        if ($request->hasFile('avatar')) {
            // Delete old avatar if exists in storage
            if ($user->avatar && \Illuminate\Support\Facades\Storage::disk('public')->exists($user->avatar)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($user->avatar);
            }

            $path = $request->file('avatar')->store('avatars', 'public');
            $user->update(['avatar' => $path]);

            return response()->json([
                'message' => 'Avatar updated successfully.',
                'avatar' => asset('storage/' . $path),
                'user' => $user->fresh(),
            ]);
        }

        return response()->json(['message' => 'No file uploaded.'], 400);
    }

    /**
     * Update the authenticated user's banner (cover) image.
     */
    public function uploadBanner(Request $request): JsonResponse
    {
        $request->validate([
            'banner' => 'required|image|max:10240', // 10MB max
        ]);

        $user = $request->user();
        if ($request->hasFile('banner')) {
            // Delete old banner if exists
            if ($user->banner_image && \Illuminate\Support\Facades\Storage::disk('public')->exists($user->banner_image)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($user->banner_image);
            }

            $path = $request->file('banner')->store('banners', 'public');
            $user->update(['banner_image' => $path]);

            return response()->json([
                'message' => 'Banner updated successfully.',
                'banner' => asset('storage/' . $path),
                'user' => $user->fresh(),
            ]);
        }

        return response()->json(['message' => 'No file uploaded.'], 400);
    }
}
