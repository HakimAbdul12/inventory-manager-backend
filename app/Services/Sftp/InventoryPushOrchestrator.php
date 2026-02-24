<?php

namespace App\Services\Sftp;

use App\Models\InventoryItem;
use App\Models\InventoryPushHistory;
use App\Models\InventoryPushJob;
use App\Models\SftpConnection;
use App\Models\Tenant;
use Illuminate\Support\Facades\Log;

class InventoryPushOrchestrator
{
    public function __construct(
        private SftpService $sftpService,
        private InventoryExportService $exportService,
    ) {}

    /**
     * Execute an inventory push: filter → export → upload → log.
     */
    public function execute(InventoryPushJob $pushJob, string $triggeredBy = 'system'): InventoryPushHistory
    {
        // Create history record
        $history = InventoryPushHistory::create([
            'tenant_id' => $pushJob->tenant_id,
            'push_job_id' => $pushJob->id,
            'triggered_by' => $triggeredBy,
            'file_format' => $pushJob->file_format,
            'status' => 'pending',
            'target_connections' => $pushJob->sftp_connection_ids,
        ]);

        $history->markRunning();
        $localFilePath = null;

        try {
            // 1. Query filtered inventory
            $items = $this->queryInventory($pushJob);

            if ($items->isEmpty()) {
                $history->markCompleted('success', 0, 'No matching inventory items found.');
                return $history;
            }

            // 2. Resolve fields from categories
            $fields = $this->exportService->resolveFields($pushJob->category_ids);

            // 3. Generate export file
            $localFilePath = $this->exportService->export($items, $fields, $pushJob->file_format);

            $fileName = basename($localFilePath);
            $history->update(['file_name' => $fileName]);

            // 4. Upload to each SFTP connection
            $connections = $this->resolveConnections($pushJob);
            $connectionResults = [];
            $successCount = 0;
            $failCount = 0;

            foreach ($connections as $connection) {
                $remotePath = $this->resolveRemotePath($pushJob, $connection, $fileName);
                $result = $this->sftpService->uploadFile($connection, $localFilePath, $remotePath);

                $connectionResults[] = [
                    'connection_id' => $connection->id,
                    'connection_name' => $connection->name,
                    'host' => $connection->host,
                    'remote_path' => $remotePath,
                    'success' => $result['success'],
                    'message' => $result['message'],
                ];

                $result['success'] ? $successCount++ : $failCount++;
            }

            // 5. Determine overall status
            $totalConnections = count($connections);
            if ($successCount === $totalConnections) {
                $status = 'success';
                $error = null;
            } elseif ($successCount > 0) {
                $status = 'partial';
                $error = "{$failCount} of {$totalConnections} transfers failed.";
            } else {
                $status = 'failed';
                $error = 'All transfers failed.';
            }

            $history->markCompleted($status, $items->count(), $error, $connectionResults);

            // 6. Update push job timestamps
            $pushJob->update([
                'last_run_at' => now(),
                'next_run_at' => $pushJob->calculateNextRunAt(),
            ]);

            Log::info('Inventory push completed', [
                'push_job_id' => $pushJob->id,
                'status' => $status,
                'records' => $items->count(),
                'connections' => $totalConnections,
            ]);
        } catch (\Throwable $e) {
            Log::error('Inventory push failed', [
                'push_job_id' => $pushJob->id,
                'error' => $e->getMessage(),
            ]);

            $history->markCompleted('failed', 0, $e->getMessage());
        } finally {
            // 7. Clean up temp file
            if ($localFilePath && file_exists($localFilePath)) {
                unlink($localFilePath);
            }
        }

        return $history;
    }

    /**
     * Query inventory items using the push job's filters.
     */
    private function queryInventory(InventoryPushJob $pushJob): \Illuminate\Support\Collection
    {
        $query = InventoryItem::query();

        // Filter by categories
        if (!empty($pushJob->category_ids)) {
            $query->whereIn('category_id', $pushJob->category_ids);
        }

        // Apply dynamic filters from JSON
        $filters = $pushJob->filters ?? [];

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['condition'])) {
            $query->whereJsonContains('generated_data->condition', $filters['condition']);
        }

        if (!empty($filters['make'])) {
            $query->where('generated_data->make', 'like', '%' . $filters['make'] . '%');
        }

        if (!empty($filters['model'])) {
            $query->where('generated_data->model', 'like', '%' . $filters['model'] . '%');
        }

        if (!empty($filters['min_year'])) {
            $query->whereRaw("CAST(JSON_UNQUOTE(JSON_EXTRACT(generated_data, '$.year')) AS UNSIGNED) >= ?", [$filters['min_year']]);
        }

        if (!empty($filters['max_year'])) {
            $query->whereRaw("CAST(JSON_UNQUOTE(JSON_EXTRACT(generated_data, '$.year')) AS UNSIGNED) <= ?", [$filters['max_year']]);
        }

        if (!empty($filters['min_price'])) {
            $query->whereRaw("CAST(JSON_UNQUOTE(JSON_EXTRACT(generated_data, '$.price')) AS DECIMAL(12,2)) >= ?", [$filters['min_price']]);
        }

        if (!empty($filters['max_price'])) {
            $query->whereRaw("CAST(JSON_UNQUOTE(JSON_EXTRACT(generated_data, '$.price')) AS DECIMAL(12,2)) <= ?", [$filters['max_price']]);
        }

        if (!empty($filters['min_mileage'])) {
            $query->whereRaw("CAST(JSON_UNQUOTE(JSON_EXTRACT(generated_data, '$.mileage')) AS UNSIGNED) >= ?", [$filters['min_mileage']]);
        }

        if (!empty($filters['max_mileage'])) {
            $query->whereRaw("CAST(JSON_UNQUOTE(JSON_EXTRACT(generated_data, '$.mileage')) AS UNSIGNED) <= ?", [$filters['max_mileage']]);
        }

        return $query->get();
    }

    /**
     * Resolve SFTP connection models for the push job.
     */
    private function resolveConnections(InventoryPushJob $pushJob): \Illuminate\Support\Collection
    {
        $ids = $pushJob->sftp_connection_ids ?? [];
        if (empty($ids)) {
            throw new \RuntimeException('No SFTP connections configured for this push job.');
        }

        return SftpConnection::whereIn('id', $ids)->active()->get();
    }

    /**
     * Build the remote file path (relative to adapter root).
     *
     * The Flysystem adapter is already rooted at connection->default_remote_path,
     * so all paths here must be RELATIVE — never re-include the remote path.
     */
    private function resolveRemotePath(InventoryPushJob $pushJob, SftpConnection $connection, string $generatedFileName): string
    {
        // Use custom filename if set, otherwise the generated one
        if (!empty($pushJob->custom_filename)) {
            $ext = pathinfo($generatedFileName, PATHINFO_EXTENSION);
            $fileName = $pushJob->custom_filename . '.' . $ext;
        } else {
            $fileName = $generatedFileName;
        }

        // If there's a destination folder override, nest inside it (relative)
        if (!empty($pushJob->destination_folder_override)) {
            $folder = trim($pushJob->destination_folder_override, '/');
            return $folder . '/' . $fileName;
        }

        // Otherwise upload straight to the adapter root
        return $fileName;
    }
}
