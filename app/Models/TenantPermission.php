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
}
