<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'dealer_code',
        'company_name',
        'phone',
        'blocked_at',
        'avatar',
    ];

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::creating(function (User $user) {
            $user->dealer_code = $user->generateUniqueDealerCode();
        });
    }

    /**
     * Generate a unique dealer code.
     */
    public function generateUniqueDealerCode(): string
    {
        do {
            $code = strtoupper(Str::random(8));
        } while (User::where('dealer_code', $code)->exists());

        return $code;
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'blocked_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the user's inventory processes.
     */
    public function inventoryProcesses(): HasMany
    {
        return $this->hasMany(InventoryProcess::class, 'user_id');
    }

    /**
     * Get the user's inventory items.
     */
    public function inventoryItems(): HasMany
    {
        return $this->hasMany(InventoryItem::class, 'user_id');
    }

    /**
     * Get the user's imports.
     */
    public function imports(): HasMany
    {
        return $this->hasMany(Import::class, 'user_id');
    }
}
