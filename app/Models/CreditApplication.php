<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class CreditApplication extends Model
{
    use HasFactory, HasUuids, BelongsToTenant;

    // ── Status Constants ─────────────────────────────────────
    public const STATUS_NOT_SENT  = 'not_sent';
    public const STATUS_SENT      = 'sent';
    public const STATUS_OPENED    = 'opened';
    public const STATUS_SUBMITTED = 'submitted';

    public const STATUSES = [
        self::STATUS_NOT_SENT,
        self::STATUS_SENT,
        self::STATUS_OPENED,
        self::STATUS_SUBMITTED,
    ];

    protected $fillable = [
        'tenant_id',
        'lead_id',
        'token',
        'status',
        'is_active',
        'application_data',
        'sent_at',
        'opened_at',
        'submitted_at',
        'esignature_name',
        'esignature_date',
        'created_by',
        'reactivated_by',
        'reactivated_at',
    ];

    protected $casts = [
        'application_data' => 'array',
        'is_active'        => 'boolean',
        'sent_at'          => 'datetime',
        'opened_at'        => 'datetime',
        'submitted_at'     => 'datetime',
        'reactivated_at'   => 'datetime',
        'esignature_date'  => 'date',
    ];

    protected $attributes = [
        'status'    => self::STATUS_NOT_SENT,
        'is_active' => true,
    ];

    // ── Relationships ────────────────────────────────────────

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reactivatedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reactivated_by');
    }

    // ── SSN Encryption ───────────────────────────────────────

    /**
     * Encrypt SSN within the application data before saving.
     */
    public function setApplicationDataAttribute($value): void
    {
        if (is_array($value)) {
            // Encrypt primary applicant SSN
            if (!empty($value['personal_info']['ssn'])) {
                $value['personal_info']['ssn_encrypted'] = Crypt::encryptString($value['personal_info']['ssn']);
                $value['personal_info']['ssn_last4'] = substr($value['personal_info']['ssn'], -4);
                unset($value['personal_info']['ssn']);
            }

            // Encrypt co-applicant SSN (flat structure from wizard)
            if (!empty($value['co_applicant']['ssn'])) {
                $value['co_applicant']['ssn_encrypted'] = Crypt::encryptString($value['co_applicant']['ssn']);
                $value['co_applicant']['ssn_last4'] = substr($value['co_applicant']['ssn'], -4);
                unset($value['co_applicant']['ssn']);
            }

            // Also handle nested co-applicant structure
            if (!empty($value['co_applicant']['personal_info']['ssn'])) {
                $value['co_applicant']['personal_info']['ssn_encrypted'] = Crypt::encryptString($value['co_applicant']['personal_info']['ssn']);
                $value['co_applicant']['personal_info']['ssn_last4'] = substr($value['co_applicant']['personal_info']['ssn'], -4);
                unset($value['co_applicant']['personal_info']['ssn']);
            }
        }

        $this->attributes['application_data'] = is_array($value) ? json_encode($value) : $value;
    }

    /**
     * Get the masked application data (SSN shows last 4 only).
     */
    public function getMaskedApplicationDataAttribute(): ?array
    {
        $data = $this->application_data;
        if (!$data) return null;

        // Replace encrypted SSN with masked version
        if (!empty($data['personal_info']['ssn_last4'])) {
            $data['personal_info']['ssn_display'] = '***-**-' . $data['personal_info']['ssn_last4'];
            unset($data['personal_info']['ssn_encrypted']);
        }

        // Flat co-applicant structure
        if (!empty($data['co_applicant']['ssn_last4'])) {
            $data['co_applicant']['ssn_display'] = '***-**-' . $data['co_applicant']['ssn_last4'];
            unset($data['co_applicant']['ssn_encrypted']);
        }

        // Nested co-applicant structure
        if (!empty($data['co_applicant']['personal_info']['ssn_last4'])) {
            $data['co_applicant']['personal_info']['ssn_display'] = '***-**-' . $data['co_applicant']['personal_info']['ssn_last4'];
            unset($data['co_applicant']['personal_info']['ssn_encrypted']);
        }

        return $data;
    }

    /**
     * Decrypt and return the full SSN (for PDF or authorized views only).
     */
    public function decryptSsn(string $path = 'personal_info'): ?string
    {
        $data = $this->application_data;
        $encrypted = data_get($data, "{$path}.ssn_encrypted");

        if (!$encrypted) return null;

        try {
            return Crypt::decryptString($encrypted);
        } catch (\Throwable) {
            return null;
        }
    }

    // ── Public URL ───────────────────────────────────────────

    public function getPublicUrlAttribute(): string
    {
        $frontendUrl = config('app.frontend_url', 'http://localhost:3000');
        return "{$frontendUrl}/apply/{$this->token}";
    }

    // ── Status Helpers ───────────────────────────────────────

    public function isSubmitted(): bool
    {
        return $this->status === self::STATUS_SUBMITTED;
    }

    public function isActive(): bool
    {
        return $this->is_active;
    }

    public function markAsSent(): void
    {
        if (!$this->sent_at) {
            $this->update([
                'status'  => self::STATUS_SENT,
                'sent_at' => now(),
            ]);
        } else {
            $this->update(['status' => self::STATUS_SENT]);
        }
    }

    public function markAsOpened(): void
    {
        if (!$this->opened_at) {
            $this->update([
                'status'    => self::STATUS_OPENED,
                'opened_at' => now(),
            ]);
        }
    }

    public function markAsSubmitted(array $applicationData, string $esignatureName): void
    {
        $this->application_data = $applicationData;
        $this->status = self::STATUS_SUBMITTED;
        $this->is_active = false;
        $this->submitted_at = now();
        $this->esignature_name = $esignatureName;
        $this->esignature_date = now()->toDateString();
        $this->save();
    }
}
