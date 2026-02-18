<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class TenantUser extends Pivot
{
    protected $table = 'tenant_user';

    protected $casts = [
        'joined_at' => 'datetime',
    ];

    // ─── Role Constants ─────────────────────────────────────

    public const ROLE_OWNER = 'owner';
    public const ROLE_ADMIN = 'admin';
    public const ROLE_MANAGER = 'manager';
    public const ROLE_CLERK = 'clerk';
    public const ROLE_VIEWER = 'viewer';

    /**
     * Ordered from most to least privileged.
     */
    public const ROLE_HIERARCHY = [
        self::ROLE_OWNER => 50,
        self::ROLE_ADMIN => 40,
        self::ROLE_MANAGER => 30,
        self::ROLE_CLERK => 20,
        self::ROLE_VIEWER => 10,
    ];

    // ─── Relationships ──────────────────────────────────────

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ─── Helpers ─────────────────────────────────────────────

    public static function roleLevel(string $role): int
    {
        return self::ROLE_HIERARCHY[$role] ?? 0;
    }

    public function isAtLeast(string $requiredRole): bool
    {
        return self::roleLevel($this->role) >= self::roleLevel($requiredRole);
    }
}
