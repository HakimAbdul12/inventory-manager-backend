<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class TenantRole extends Model
{
    use HasFactory, HasUuids, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'name',
        'slug',
        'description',
        'is_system',
        'level',
    ];

    protected $casts = [
        'is_system' => 'boolean',
        'level' => 'integer',
    ];

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(
            TenantPermission::class,
            'tenant_role_permissions',
            'tenant_role_id',
            'tenant_permission_id'
        );
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'tenant_user_roles',
            'tenant_role_id',
            'user_id'
        )->using(TenantUserRole::class)->withPivot(['id', 'tenant_id', 'assigned_by'])->withTimestamps();
    }

    /**
     * Check if this role has a specific permission.
     */
    public function hasPermission(string $key): bool
    {
        // For performance in real apps, this should use loaded relations or cache
        return $this->permissions()->where('key', $key)->exists();
    }
}
