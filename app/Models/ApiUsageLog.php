<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApiUsageLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'method',
        'path',
        'status_code',
        'duration_ms',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'duration_ms' => 'float',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
