<?php

namespace App\Jobs;

use App\Models\InventoryPushJob;
use App\Models\Tenant;
use App\Services\Sftp\InventoryPushOrchestrator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ExecuteInventoryPushJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public $tries = 1;

    /**
     * The number of seconds the job can run before timing out.
     */
    public $timeout = 600; // 10 minutes

    /**
     * The unique lock will be released after 15 minutes.
     */
    public $uniqueFor = 900;

    public function __construct(
        public string $pushJobId,
        public string $tenantId,
        public string $triggeredBy = 'system',
    ) {}

    /**
     * The unique ID for preventing duplicate runs.
     */
    public function uniqueId(): string
    {
        return 'push_job_' . $this->pushJobId;
    }

    public function handle(InventoryPushOrchestrator $orchestrator): void
    {
        // Bind tenant context for global scopes
        $tenant = Tenant::findOrFail($this->tenantId);
        app()->instance('current_tenant', $tenant);

        $pushJob = InventoryPushJob::findOrFail($this->pushJobId);

        Log::info('Executing inventory push job', [
            'push_job_id' => $this->pushJobId,
            'tenant_id' => $this->tenantId,
            'triggered_by' => $this->triggeredBy,
        ]);

        $orchestrator->execute($pushJob, $this->triggeredBy);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Inventory push job failed permanently', [
            'push_job_id' => $this->pushJobId,
            'error' => $exception->getMessage(),
        ]);
    }
}
