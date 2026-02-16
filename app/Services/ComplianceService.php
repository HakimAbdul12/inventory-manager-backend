<?php

namespace App\Services;

use App\Models\Vehicle;
use App\Models\ComplianceAudit;

class ComplianceService
{
    protected array $prohibitedTerms = [
        'guaranteed credit',
        'lowest price',
        'no money down',
        'zero percent',
        'out the door', // Sometimes flag for verification
    ];

    /**
     * Scan vehicle description for compliance.
     */
    public function scan(Vehicle $vehicle, string $content): ComplianceAudit
    {
        $violations = [];
        $lowerContent = strtolower($content);

        foreach ($this->prohibitedTerms as $term) {
            if (str_contains($lowerContent, $term)) {
                $violations[] = "Used prohibited term: '{$term}'";
            }
        }

        // Check for "Out the Door" pricing requirement
        if (!str_contains($lowerContent, 'out the door') && !str_contains($lowerContent, 'otd')) {
            // In some states, this is required if price is mentioned
            // For now, just a logic placeholder
        }

        $isCompliant = empty($violations);

        return ComplianceAudit::create([
            'vehicle_id' => $vehicle->id,
            'scanned_content' => $content,
            'is_compliant' => $isCompliant,
            'violations' => $violations,
            'remediation_suggestions' => $isCompliant ? null : 'Remove prohibited marketing terms.',
        ]);
    }
}
