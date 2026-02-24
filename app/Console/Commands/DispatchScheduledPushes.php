<?php

namespace App\Console\Commands;

use App\Jobs\ExecuteInventoryPushJob;
use App\Models\InventoryPushJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class DispatchScheduledPushes extends Command
{
    protected $signature = 'sftp:dispatch-scheduled-pushes {--time= : The schedule time to dispatch (00:00 or 12:00)}';

    protected $description = 'Dispatch all scheduled inventory push jobs for the given time slot.';

    public function handle(): int
    {
        $time = $this->option('time');

        if (!in_array($time, ['00:00', '12:00'])) {
            $this->error('Invalid time. Must be 00:00 or 12:00.');
            return self::FAILURE;
        }

        // Query across all tenants
        $jobs = InventoryPushJob::withoutGlobalScope('tenant')
            ->active()
            ->scheduled()
            ->forScheduleTime($time)
            ->get();

        $count = $jobs->count();
        $this->info("Found {$count} scheduled push jobs for {$time}.");

        foreach ($jobs as $pushJob) {
            ExecuteInventoryPushJob::dispatch(
                $pushJob->id,
                $pushJob->tenant_id,
                'system'
            )->onQueue('inventory');

            $this->line("  → Dispatched: {$pushJob->name} (tenant: {$pushJob->tenant_id})");
        }

        Log::info("Dispatched {$count} scheduled push jobs for {$time}.");

        return self::SUCCESS;
    }
}
