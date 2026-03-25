<?php

namespace App\Http\Controllers\Api;

use App\Events\NewTestDriveBooked;
use App\Models\TestDrive;
use App\Models\WorkspaceChatConfig;
use App\Services\Chat\TestDriveService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class TestDriveController extends Controller
{
    public function __construct(
        protected TestDriveService $testDriveService
    ) {}

    // ── Dashboard (Protected) ────────────────────────────────

    /**
     * List test drives with filters (paginated).
     */
    public function index(Request $request): JsonResponse
    {
        $tenantId = $request->header('X-Tenant-ID');

        $query = TestDrive::where('tenant_id', $tenantId)
            ->orderBy('scheduled_date', 'desc')
            ->orderBy('scheduled_time', 'desc');

        // Filter by date range
        if ($request->filled('from_date')) {
            $query->where('scheduled_date', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->where('scheduled_date', '<=', $request->to_date);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by vehicle
        if ($request->filled('vehicle_id')) {
            $query->where('vehicle_id', $request->vehicle_id);
        }

        // Search by customer
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('visitor_name', 'like', "%{$search}%")
                    ->orWhere('visitor_email', 'like', "%{$search}%")
                    ->orWhere('visitor_phone', 'like', "%{$search}%")
                    ->orWhere('booking_code', 'like', "%{$search}%");
            });
        }

        $testDrives = $query->with('vehicle:id,make,model,year,stock_number')
            ->paginate($request->input('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $testDrives->items(),
            'pagination' => [
                'currentPage' => $testDrives->currentPage(),
                'lastPage' => $testDrives->lastPage(),
                'perPage' => $testDrives->perPage(),
                'total' => $testDrives->total(),
            ],
        ]);
    }

    /**
     * Show a single test drive.
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $tenantId = $request->header('X-Tenant-ID');

        $testDrive = TestDrive::where('tenant_id', $tenantId)
            ->with(['vehicle', 'conversation'])
            ->findOrFail($id);

        return response()->json(['success' => true, 'data' => $testDrive]);
    }

    /**
     * Update test drive status from dashboard.
     */
    public function updateStatus(Request $request, string $id): JsonResponse
    {
        $tenantId = $request->header('X-Tenant-ID');

        $validated = $request->validate([
            'status' => 'required|in:' . implode(',', TestDrive::STATUSES),
            'notes' => 'sometimes|nullable|string',
        ]);

        $testDrive = TestDrive::where('tenant_id', $tenantId)->findOrFail($id);
        $testDrive->update($validated);

        return response()->json(['success' => true, 'data' => $testDrive->fresh()]);
    }

    // ── Widget (Public) ──────────────────────────────────────

    /**
     * Get available time slots for the widget.
     */
    public function availableSlots(Request $request, string $apiKey): JsonResponse
    {
        $config = WorkspaceChatConfig::where('widget_api_key', $apiKey)->firstOrFail();
        $tenantId = $config->tenant_id;

        $slots = $this->testDriveService->getAvailableSlots(
            $tenantId,
            $request->query('from_date'),
            (int) $request->query('days', 7)
        );

        return response()->json(['success' => true, 'data' => $slots]);
    }

    /**
     * Book a test drive from the widget.
     */
    public function book(Request $request, string $apiKey): JsonResponse
    {
        $config = WorkspaceChatConfig::where('widget_api_key', $apiKey)->firstOrFail();
        $tenantId = $config->tenant_id;

        $validated = $request->validate([
            'date' => 'required|date|after_or_equal:today',
            'time' => 'required|date_format:H:i',
            'name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'vehicle_id' => 'nullable|uuid',
            'conversation_id' => 'nullable|uuid',
            'session_token' => 'nullable|string',
        ]);

        $validated['tenant_id'] = $tenantId;

        try {
            $testDrive = $this->testDriveService->bookTestDrive($tenantId, $validated);
            event(new NewTestDriveBooked($testDrive));

            return response()->json([
                'success' => true,
                'data' => [
                    'booking_code' => $testDrive->booking_code,
                    'scheduled_date' => $testDrive->scheduled_date->toDateString(),
                    'scheduled_time' => $testDrive->scheduled_time,
                    'end_time' => $testDrive->end_time,
                    'status' => $testDrive->status,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Reschedule a test drive from the widget.
     */
    public function reschedule(Request $request, string $apiKey): JsonResponse
    {
        $validated = $request->validate([
            'booking_code' => 'required|string|size:6',
            'new_date' => 'required|date|after_or_equal:today',
            'new_time' => 'required|date_format:H:i',
        ]);

        try {
            $testDrive = $this->testDriveService->rescheduleTestDrive(
                $validated['booking_code'],
                $validated['new_date'],
                $validated['new_time']
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'booking_code' => $testDrive->booking_code,
                    'scheduled_date' => $testDrive->scheduled_date->toDateString(),
                    'scheduled_time' => $testDrive->scheduled_time,
                    'end_time' => $testDrive->end_time,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Cancel a test drive from the widget.
     */
    public function cancel(Request $request, string $apiKey): JsonResponse
    {
        $validated = $request->validate([
            'booking_code' => 'required|string|size:6',
            'reason' => 'nullable|string|max:500',
        ]);

        try {
            $testDrive = $this->testDriveService->cancelTestDrive(
                $validated['booking_code'],
                $validated['reason'] ?? null
            );

            return response()->json([
                'success' => true,
                'message' => 'Test drive cancelled successfully.',
                'data' => ['booking_code' => $testDrive->booking_code],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Look up a test drive by booking code from the widget.
     */
    public function lookup(Request $request, string $apiKey): JsonResponse
    {
        $validated = $request->validate([
            'booking_code' => 'required|string|size:6',
        ]);

        $testDrive = $this->testDriveService->lookupTestDrive($validated['booking_code']);

        if (!$testDrive) {
            return response()->json([
                'success' => false,
                'message' => 'No test drive found with that code.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'booking_code' => $testDrive->booking_code,
                'visitor_name' => $testDrive->visitor_name,
                'scheduled_date' => $testDrive->scheduled_date->toDateString(),
                'scheduled_time' => $testDrive->scheduled_time,
                'end_time' => $testDrive->end_time,
                'status' => $testDrive->status,
                'vehicle' => $testDrive->vehicle ? [
                    'make' => $testDrive->vehicle->make ?? null,
                    'model' => $testDrive->vehicle->model ?? null,
                    'year' => $testDrive->vehicle->year ?? null,
                ] : null,
            ],
        ]);
    }
}
