<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Import extends Model
{
    protected $fillable = [
        'user_id',
        'file_path',
        'file_name',
        'total_rows',
        'processed_rows',
        'status',
        'category_slug',
        'mappings',
        'errors',
    ];

    protected $casts = [
        'mappings' => 'array',
        'errors' => 'array',
    ];

    /**
     * Get the user that owns the import.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
