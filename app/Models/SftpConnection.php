<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class SftpConnection extends Model
{
    use HasFactory, HasUuids, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'name',
        'host',
        'port',
        'username',
        'encrypted_password',
        'encrypted_private_key',
        'auth_type',
        'default_remote_path',
        'is_active',
        'last_tested_at',
        'last_test_status',
        'last_test_message',
    ];

    protected $casts = [
        'port' => 'integer',
        'is_active' => 'boolean',
        'last_tested_at' => 'datetime',
    ];

    /**
     * Never expose credentials in JSON/array serialization.
     */
    protected $hidden = [
        'encrypted_password',
        'encrypted_private_key',
    ];

    // ─── Credential Mutators ────────────────────────────────

    public function setPasswordAttribute(?string $value): void
    {
        $this->attributes['encrypted_password'] = $value ? Crypt::encryptString($value) : null;
    }

    public function getPasswordAttribute(): ?string
    {
        return $this->encrypted_password ? Crypt::decryptString($this->encrypted_password) : null;
    }

    public function setPrivateKeyAttribute(?string $value): void
    {
        $this->attributes['encrypted_private_key'] = $value ? Crypt::encryptString($value) : null;
    }

    public function getPrivateKeyAttribute(): ?string
    {
        return $this->encrypted_private_key ? Crypt::decryptString($this->encrypted_private_key) : null;
    }

    // ─── Scopes ─────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // ─── Helpers ────────────────────────────────────────────

    /**
     * Update test results after a connection test.
     */
    public function recordTestResult(bool $success, ?string $message = null): void
    {
        $this->update([
            'last_tested_at' => now(),
            'last_test_status' => $success ? 'success' : 'failed',
            'last_test_message' => $message,
        ]);
    }
}
