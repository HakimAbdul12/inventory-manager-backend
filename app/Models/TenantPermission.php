<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class TenantPermission extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'key',
        'label',
        'description',
        'category',
        'type', // 'high' or 'low'
    ];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            TenantRole::class,
            'tenant_role_permissions',
            'tenant_permission_id',
            'tenant_role_id'
        );
    }

    public function scopeInCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }

    /**
     * Scope to only low-level (system) permissions.
     */
    public function scopeLowLevel(Builder $query): Builder
    {
        return $query->where('type', 'low');
    }

    /**
     * Scope to only high-level (tenant) permissions.
     */
    public function scopeHighLevel(Builder $query): Builder
    {
        return $query->where('type', 'high');
    }
}
