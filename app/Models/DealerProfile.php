<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DealerProfile extends Model
{
    protected $fillable = [
        'user_id',
        'service_area',
        'certifications',
        'accepting_partnerships',
        'is_bulk_trader',
        'is_verified',
        'verification_badges',
        'reputation_score',
        'response_time_hours',
    ];

    protected function casts(): array
    {
        return [
            'service_area' => 'array',
            'certifications' => 'array',
            'verification_badges' => 'array',
            'accepting_partnerships' => 'boolean',
            'is_bulk_trader' => 'boolean',
            'is_verified' => 'boolean',
            'reputation_score' => 'decimal:2',
            'response_time_hours' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
