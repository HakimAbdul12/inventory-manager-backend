<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Import extends Model
{
    protected $fillable = [
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
}
