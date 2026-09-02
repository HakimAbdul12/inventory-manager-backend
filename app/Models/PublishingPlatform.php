<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PublishingPlatform extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'key',
        'name',
        'description',
        'icon_url',
        'color',
        'supported_types',
        'is_active',
        'sort_order',
        'settings',
    ];

    protected $casts = [
        'supported_types' => 'array',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'settings' => 'array',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
