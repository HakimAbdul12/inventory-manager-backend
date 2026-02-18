<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Tenant extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'name',
        'slug',
        'logo',
        'banner_image',
        'description',
        'owner_id',
        'settings',
    ];

    protected $casts = [
        'settings' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (Tenant $tenant) {
            if (empty($tenant->slug)) {
                $tenant->slug = Str::slug($tenant->name) . '-' . Str::lower(Str::random(6));
            }
        });
    }

    // ─── Relationships ──────────────────────────────────────

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'tenant_user')
            ->withPivot(['role', 'joined_at'])
            ->withTimestamps();
    }

    public function inventoryItems(): HasMany
    {
        return $this->hasMany(InventoryItem::class);
    }

    public function inventoryProcesses(): HasMany
    {
        return $this->hasMany(InventoryProcess::class);
    }

    public function imports(): HasMany
    {
        return $this->hasMany(Import::class);
    }

    public function transfers(): HasMany
    {
        return $this->hasMany(Transfer::class);
    }

    public function virtualShowrooms(): HasMany
    {
        return $this->hasMany(VirtualShowroom::class);
    }

    // ─── Member Management ──────────────────────────────────

    public function addMember(User $user, string $role = 'viewer'): void
    {
        if (!$this->users()->where('user_id', $user->id)->exists()) {
            $this->users()->attach($user->id, [
                'role' => $role,
                'joined_at' => now(),
            ]);
        }
    }

    public function removeMember(User $user): void
    {
        $this->users()->detach($user->id);
    }

    public function updateMemberRole(User $user, string $role): void
    {
        $this->users()->updateExistingPivot($user->id, ['role' => $role]);
    }

    public function getMemberRole(User $user): ?string
    {
        $member = $this->users()->where('user_id', $user->id)->first();
        return $member?->pivot?->role;
    }

    public function hasMember(User $user): bool
    {
        return $this->users()->where('user_id', $user->id)->exists();
    }

    public function getMemberCount(): int
    {
        return $this->users()->count();
    }

    // ─── Role Checks ────────────────────────────────────────

    public static function validRoles(): array
    {
        return ['owner', 'admin', 'manager', 'clerk', 'viewer'];
    }

    /**
     * Roles that have admin-level access.
     */
    public static function adminRoles(): array
    {
        return ['owner', 'admin'];
    }

    /**
     * Roles that can manage inventory.
     */
    public static function managementRoles(): array
    {
        return ['owner', 'admin', 'manager'];
    }

    /**
     * Roles that can create/edit resources.
     */
    public static function editorRoles(): array
    {
        return ['owner', 'admin', 'manager', 'clerk'];
    }
}
