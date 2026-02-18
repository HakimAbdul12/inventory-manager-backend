<?php

namespace App\Models\Traits;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Trait to automatically scope Eloquent models to the current tenant.
 *
 * Apply this to any model that has a `tenant_id` column.
 * It will:
 * 1. Auto-filter queries by tenant_id (global scope)
 * 2. Auto-set tenant_id on creating
 */
trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        // Auto-scope all queries to current tenant
        static::addGlobalScope('tenant', function (Builder $builder) {
            $tenant = app()->bound('current_tenant') ? app('current_tenant') : null;
            if ($tenant) {
                $builder->where($builder->getModel()->getTable() . '.tenant_id', $tenant->id);
            }
        });

        // Auto-set tenant_id on creating
        static::creating(function ($model) {
            if (empty($model->tenant_id)) {
                $tenant = app()->bound('current_tenant') ? app('current_tenant') : null;
                if ($tenant) {
                    $model->tenant_id = $tenant->id;
                }
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Query without tenant scoping (for admin/cross-tenant operations).
     */
    public function scopeWithoutTenantScope(Builder $builder): Builder
    {
        return $builder->withoutGlobalScope('tenant');
    }
}
