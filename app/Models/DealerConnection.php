<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DealerConnection extends Model
{
    protected $fillable = [
        'sender_id',
        'receiver_id',
        'status',
        'connection_level',
        'message',
    ];

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    /**
     * Check if two users are connected.
     */
    public static function areConnected(int $userA, int $userB): bool
    {
        return self::where(function ($q) use ($userA, $userB) {
            $q->where('sender_id', $userA)->where('receiver_id', $userB);
        })->orWhere(function ($q) use ($userA, $userB) {
            $q->where('sender_id', $userB)->where('receiver_id', $userA);
        })->where('status', 'accepted')->exists();
    }

    /**
     * Get mutual connections between two users.
     */
    public static function mutualConnections(int $userA, int $userB): array
    {
        $connectionsA = self::where('status', 'accepted')
            ->where(fn($q) => $q->where('sender_id', $userA)->orWhere('receiver_id', $userA))
            ->get()
            ->map(fn($c) => $c->sender_id === $userA ? $c->receiver_id : $c->sender_id)
            ->toArray();

        $connectionsB = self::where('status', 'accepted')
            ->where(fn($q) => $q->where('sender_id', $userB)->orWhere('receiver_id', $userB))
            ->get()
            ->map(fn($c) => $c->sender_id === $userB ? $c->receiver_id : $c->sender_id)
            ->toArray();

        return array_values(array_intersect($connectionsA, $connectionsB));
    }
}
