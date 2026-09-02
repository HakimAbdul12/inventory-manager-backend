<?php

namespace App\Services\Mcp\Tools;

use App\Contracts\McpTool;
use App\Events\NewTestDriveBooked;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Chat\TestDriveService;

class BookTestDriveTool implements McpTool
{
    protected TestDriveService $testDriveService;

    public function __construct(TestDriveService $testDriveService)
    {
        $this->testDriveService = $testDriveService;
    }

    public function name(): string
    {
        return 'book_test_drive';
    }

    public function description(): string
    {
        return 'Book a test drive appointment for a customer. Requires a date and time. Optionally provide customer contact details.';
    }

    public function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'date' => [
                    'type' => 'string',
                    'description' => 'Date in YYYY-MM-DD format.',
                ],
                'time' => [
                    'type' => 'string',
                    'description' => 'Time in HH:MM (24h) format.',
                ],
                'name' => [
                    'type' => 'string',
                    'description' => 'Customer name.',
                ],
                'email' => [
                    'type' => 'string',
                    'description' => 'Customer email.',
                ],
                'phone' => [
                    'type' => 'string',
                    'description' => 'Customer phone number.',
                ],
                'vehicle_id' => [
                    'type' => 'string',
                    'description' => 'Optional inventory item ID for the vehicle they want to test drive.',
                ],
                'notes' => [
                    'type' => 'string',
                    'description' => 'Additional notes for the test drive.',
                ],
            ],
            'required' => ['date', 'time'],
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
            $testDrive = $this->testDriveService->bookTestDrive($tenant->id, $args);
            event(new NewTestDriveBooked($testDrive));

            return [
                ['type' => 'text', 'text' => json_encode([
                    'success' => true,
                    'booking_code' => $testDrive->booking_code,
                    'date' => $testDrive->scheduled_date->format('l, F jS, Y'),
                    'start_time' => $testDrive->scheduled_time,
                    'end_time' => $testDrive->end_time,
                    'message' => "Test drive booked successfully! Booking code: {$testDrive->booking_code}",
                ], JSON_PRETTY_PRINT)],
            ];
        } catch (\Exception $e) {
            return [
                ['type' => 'text', 'text' => json_encode([
                    'success' => false,
                    'error' => $e->getMessage(),
                ])],
            ];
        }
    }
}
