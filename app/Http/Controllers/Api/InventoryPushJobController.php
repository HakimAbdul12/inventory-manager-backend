<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ExecuteInventoryPushJob;
use App\Models\InventoryPushJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InventoryPushJobController extends Controller
{
    /**
     * List all push job configurations.
     */
    public function index(): JsonResponse
    {
        $jobs = InventoryPushJob::with('history')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($job) {
                $latestHistory = $job->history->sortByDesc('created_at')->first();
                return [
                    'id' => $job->id,
                    'name' => $job->name,
                    'type' => $job->type,
                    'schedule_time' => $job->schedule_time,
                    'file_format' => $job->file_format,
                    'filters' => $job->filters,
                    'category_ids' => $job->category_ids,
                    'sftp_connection_ids' => $job->sftp_connection_ids,
                    'destination_folder_override' => $job->destination_folder_override,
                    'is_active' => $job->is_active,
                    'last_run_at' => $job->last_run_at,
                    'next_run_at' => $job->next_run_at,
                    'last_status' => $latestHistory?->status,
                    'created_at' => $job->created_at,
                    'updated_at' => $job->updated_at,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $jobs,
        ]);
    }

    /**
     * Create a new push job configuration.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:one_time,scheduled',
            'schedule_time' => 'required_if:type,scheduled|nullable|in:00:00,12:00',
            'file_format' => 'required|in:csv,xml,json',
            'filters' => 'nullable|array',
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'uuid|exists:categories,id',
            'sftp_connection_ids' => 'required|array|min:1',
            'sftp_connection_ids.*' => 'uuid|exists:sftp_connections,id',
            'destination_folder_override' => 'nullable|string|max:500',
            'custom_filename' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        $pushJob = InventoryPushJob::create($validated);

        // Calculate next_run_at for scheduled jobs
        if ($pushJob->isScheduled()) {
            $pushJob->update(['next_run_at' => $pushJob->calculateNextRunAt()]);
        }

        return response()->json([
            'success' => true,
            'data' => $pushJob,
            'message' => 'Push job created successfully.',
        ], 201);
    }

    /**
     * Update an existing push job configuration.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $pushJob = InventoryPushJob::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'type' => 'sometimes|in:one_time,scheduled',
            'schedule_time' => 'nullable|in:00:00,12:00',
            'file_format' => 'sometimes|in:csv,xml,json',
            'filters' => 'nullable|array',
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'uuid|exists:categories,id',
            'sftp_connection_ids' => 'sometimes|array|min:1',
            'sftp_connection_ids.*' => 'uuid|exists:sftp_connections,id',
            'destination_folder_override' => 'nullable|string|max:500',
            'custom_filename' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        $pushJob->update($validated);

        // Recalculate next_run_at
        if ($pushJob->isScheduled()) {
            $pushJob->update(['next_run_at' => $pushJob->calculateNextRunAt()]);
        }

        return response()->json([
            'success' => true,
            'data' => $pushJob,
            'message' => 'Push job updated successfully.',
        ]);
    }

    /**
     * Delete a push job configuration.
     */
    public function destroy(string $id): JsonResponse
    {
        $pushJob = InventoryPushJob::findOrFail($id);
        $pushJob->delete();

        return response()->json([
            'success' => true,
            'message' => 'Push job deleted successfully.',
        ]);
    }

    /**
     * Manually execute a push job (dispatches to queue).
     */
    public function execute(Request $request, string $id): JsonResponse
    {
        $pushJob = InventoryPushJob::findOrFail($id);
        $tenantId = app('current_tenant')->id;
        $userId = $request->user()->id;

        ExecuteInventoryPushJob::dispatch($pushJob->id, $tenantId, $userId)
            ->onQueue('inventory');

        return response()->json([
            'success' => true,
            'message' => 'Push job queued for execution.',
        ]);
    }
}
