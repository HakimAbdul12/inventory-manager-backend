<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Tenant;
use App\Models\TenantRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'google_id',
        'dealer_code',
        'company_name',
        'phone',
        'blocked_at',
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
        'current_tenant_id',
    ];

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::creating(function (User $user) {
            $user->dealer_code = $user->generateUniqueDealerCode();
        });
    }

    /**
     * Generate a unique dealer code.
     */
    public function generateUniqueDealerCode(): string
    {
        do {
            $code = strtoupper(Str::random(8));
        } while (User::where('dealer_code', $code)->exists());

        return $code;
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'blocked_at' => 'datetime',
            'last_active_at' => 'datetime',
            'password' => 'hashed',
            'specialties' => 'array',
            'social_links' => 'array',
            'is_public_profile' => 'boolean',
        ];
    }

    /**
     * Get the user's inventory processes.
     */
    public function inventoryProcesses(): HasMany
    {
        return $this->hasMany(InventoryProcess::class, 'user_id');
    }

    /**
     * Get the user's inventory items.
     */
    public function inventoryItems(): HasMany
    {
        return $this->hasMany(InventoryItem::class, 'user_id');
    }

    /**
     * Get the user's imports.
     */
    public function imports(): HasMany
    {
        return $this->hasMany(Import::class, 'user_id');
    }

    /**
     * Get the dealer's extended profile.
     */
    public function dealerProfile(): HasOne
    {
        return $this->hasOne(DealerProfile::class);
    }

    /**
     * Connections where this user sent the request.
     */
    public function sentConnections(): HasMany
    {
        return $this->hasMany(DealerConnection::class, 'sender_id');
    }

    /**
     * Connections where this user received the request.
     */
    public function receivedConnections(): HasMany
    {
        return $this->hasMany(DealerConnection::class, 'receiver_id');
    }

    /**
     * All accepted connections (both directions).
     */
    public function connections()
    {
        return DealerConnection::where('status', 'accepted')
            ->where(fn($q) => $q->where('sender_id', $this->id)->orWhere('receiver_id', $this->id));
    }

    /**
     * Chat rooms the user is a member of.
     */
    public function chatRooms(): BelongsToMany
    {
        return $this->belongsToMany(ChatRoom::class, 'chat_room_members')
            ->withPivot(['role', 'last_read_at', 'joined_at'])
            ->withTimestamps();
    }

    /**
     * Feed posts created by this user.
     */
    public function feedPosts(): HasMany
    {
        return $this->hasMany(FeedPost::class);
    }

    // ─── Multi-Tenancy ──────────────────────────────────────

    /**
     * All tenants this user belongs to.
     */
    public function tenants(): BelongsToMany
    {
        return $this->belongsToMany(Tenant::class, 'tenant_user')
            ->withPivot(['role', 'joined_at'])
            ->withTimestamps();
    }

    /**
     * The user's currently active tenant.
     */
    public function currentTenant(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'current_tenant_id');
    }

    /**
     * Get the granular roles this user has across all tenants.
     * Note: Filter by tenant_id when querying for a specific tenant.
     */
    public function tenantRoles(): BelongsToMany
    {
        return $this->belongsToMany(
            TenantRole::class,
            'tenant_user_roles',
            'user_id',
            'tenant_role_id'
        )->withPivot(['tenant_id', 'assigned_by'])->withTimestamps();
    }

    /**
     * Switch the user's active tenant.
     */
    public function switchTenant(string $tenantId): bool
    {
        // Verify user belongs to this tenant
        if (!$this->tenants()->where('tenants.id', $tenantId)->exists()) {
            return false;
        }

        $this->update(['current_tenant_id' => $tenantId]);
        return true;
    }

    /**
     * Get the user's role in a specific tenant.
     */
    public function roleInTenant(?Tenant $tenant = null): ?string
    {
        $tenant = $tenant ?? $this->currentTenant;
        if (!$tenant) return null;

        $membership = $this->tenants()->where('tenants.id', $tenant->id)->first();
        return $membership?->pivot?->role;
    }

    /**
     * Check if the user has at least the given role in a tenant.
     */
    public function hasRoleInTenant(string $requiredRole, ?Tenant $tenant = null): bool
    {
        $currentRole = $this->roleInTenant($tenant);
        if (!$currentRole) return false;

        return TenantUser::roleLevel($currentRole) >= TenantUser::roleLevel($requiredRole);
    }

    /**
     * Get tenants formatted for API response.
     */
    public function getTenantsForApi(): array
    {
        return $this->tenants->map(fn(Tenant $tenant) => [
            'id' => $tenant->id,
            'name' => $tenant->name,
            'slug' => $tenant->slug,
            'logo' => $tenant->logo,
            'banner_image' => $tenant->banner_image,
            'role' => $tenant->pivot->role,
            'member_count' => $tenant->users()->count(),
        ])->toArray();
    }
}
