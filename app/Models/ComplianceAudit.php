<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComplianceAudit extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'vehicle_id',
        'scanned_content',
        'is_compliant',
        'violations',
        'remediation_suggestions',
    ];

    protected $casts = [
        'is_compliant' => 'boolean',
        'violations' => 'array',
    ];

    /**
     * Get the vehicle associated with this compliance audit.
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }
}
