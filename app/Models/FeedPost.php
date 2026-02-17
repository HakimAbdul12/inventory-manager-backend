<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class FeedPost extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'type',
        'content',
        'media',
        'inventory_ids',
        'likes_count',
        'comments_count',
        'shares_count',
    ];

    protected function casts(): array
    {
        return [
            'media' => 'array',
            'inventory_ids' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function interactions(): HasMany
    {
        return $this->hasMany(FeedInteraction::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(FeedInteraction::class)->where('type', 'comment')->latest();
    }

    public function likes(): HasMany
    {
        return $this->hasMany(FeedInteraction::class)->where('type', 'like');
    }
}
