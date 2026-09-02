<?php

namespace App\Services\Mcp\Tools;

use App\Contracts\McpTool;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Chat\TestDriveService;

class GetTestDriveSlotsTool implements McpTool
{
    protected TestDriveService $testDriveService;

    public function __construct(TestDriveService $testDriveService)
    {
        $this->testDriveService = $testDriveService;
    }

    public function name(): string
    {
        return 'get_test_drive_slots';
    }

    public function description(): string
    {
        return 'Get available test drive time slots for the dealership. Returns available dates and times for the next N days.';
    }

    public function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'from_date' => [
                    'type' => 'string',
                    'description' => 'Start date in YYYY-MM-DD format. Defaults to today.',
                ],
                'days' => [
                    'type' => 'integer',
                    'description' => 'Number of days to show availability for. Default 7, max 30.',
                ],
            ],
        ];
    }

    public function requiredPermission(): ?string
    {
        return null; // Tenant-scoped via session context
    }

    public function category(): string
    {
        return 'Test Drives';
    }

    public function execute(array $args, User $user, Tenant $tenant): array
    {
        try {
            $days = min($args['days'] ?? 7, 30);
            $slots = $this->testDriveService->getAvailableSlots(
                $tenant->id,
                $args['from_date'] ?? null,
                $days
            );

            if (empty($slots)) {
                return [
                    ['type' => 'text', 'text' => json_encode([
                        'available' => false,
                        'message' => 'No test drive slots available for the selected period.',
                    ])],
                ];
            }

            return [
                ['type' => 'text', 'text' => json_encode([
                    'available' => true,
                    'slots' => $slots,
                    'total_slots' => count($slots),
                ], JSON_PRETTY_PRINT)],
            ];
        } catch (\Exception $e) {
            return [
                ['type' => 'text', 'text' => json_encode([
                    'available' => false,
                    'error' => $e->getMessage(),
                ])],
            ];
        }
    }
}
