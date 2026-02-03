<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BlockedIp extends Model
{
    use HasFactory;

    protected $fillable = [
        'ip_address',
        'reason',
        'blocked_by_user_id',
    ];

    public function blockedBy()
    {
        return $this->belongsTo(User::class, 'blocked_by_user_id');
    }
}
