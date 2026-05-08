<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ChatRoom extends Model
{
    protected $fillable = ['name', 'type', 'created_by', 'avatar', 'is_favorite'];

    protected function casts(): array
    {
        return [
            'is_favorite' => 'boolean',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'chat_room_members')
            ->withPivot(['role', 'last_read_at', 'joined_at'])
            ->withTimestamps();
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class);
    }

    public function latestMessage(): HasMany
    {
        return $this->hasMany(ChatMessage::class)->latest()->limit(1);
    }

    /**
     * Find or create a direct chat room between two users.
     */
    public static function findOrCreateDirect(int $userA, int $userB): self
    {
        $room = self::where('type', 'direct')
            ->whereHas('members', fn($q) => $q->where('user_id', $userA))
            ->whereHas('members', fn($q) => $q->where('user_id', $userB))
            ->first();

        if ($room) {
            return $room;
        }

        $room = self::create([
            'type' => 'direct',
            'created_by' => $userA,
        ]);

        $room->members()->attach([
            $userA => ['role' => 'member', 'joined_at' => now()],
            $userB => ['role' => 'member', 'joined_at' => now()],
        ]);

        return $room;
    }
}
