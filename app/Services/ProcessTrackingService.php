<?php

namespace App\Services;

use App\Enums\StepStatus;
use App\Events\StepStatusUpdated;
use App\Models\InventoryProcess;
use App\Models\ProcessStep;
use Illuminate\Support\Facades\Log;

class ProcessTrackingService
{
    /**
     * Create steps for a new process based on configuration.
     */
    public function createProcessSteps(InventoryProcess $process): void
    {
        $stepsConfig = config('inventory.steps');
        $shouldGenerateImages = $process->shouldGenerateImages();

        foreach ($stepsConfig as $stepName => $stepConfig) {
            // Skip optional steps if not needed
            if (($stepConfig['optional'] ?? false) && $stepName === 'image_generation' && !$shouldGenerateImages) {
                continue;
            }

            ProcessStep::create([
                'process_id' => $process->id,
                'step_name' => $stepName,
                'step_order' => $stepConfig['order'],
                'status' => StepStatus::PENDING,
            ]);
        }

        Log::info('Created process steps', [
            'process_id' => $process->id,
            'steps_count' => $process->steps()->count(),
        ]);
    }

    /**
     * Mark a step as started.
     */
    public function startStep(ProcessStep $step): void
    {
        $step->markAsInProgress();

        $step->process->advanceToStep($step->step_name);

        $this->broadcastStepUpdate($step);

        Log::info('Step started', [
            'process_id' => $step->process_id,
            'step' => $step->step_name,
        ]);
    }

    /**
     * Mark a step as completed.
     */
    public function completeStep(ProcessStep $step, array $result = null): void
    {
        $step->markAsCompleted($result);

        $this->broadcastStepUpdate($step);

        Log::info('Step completed', [
            'process_id' => $step->process_id,
            'step' => $step->step_name,
            'duration' => $step->duration,
        ]);
    }

    /**
     * Mark a step as failed.
     */
    public function failStep(ProcessStep $step, string $error): void
    {
        $step->markAsFailed($error);

        $this->broadcastStepUpdate($step);

        Log::error('Step failed', [
            'process_id' => $step->process_id,
            'step' => $step->step_name,
            'error' => $error,
        ]);
    }

    /**
     * Mark a step as skipped.
     */
    public function skipStep(ProcessStep $step, string $reason = null): void
    {
        $step->markAsSkipped($reason);

        $this->broadcastStepUpdate($step);

        Log::info('Step skipped', [
            'process_id' => $step->process_id,
            'step' => $step->step_name,
            'reason' => $reason,
        ]);
    }

    /**
     * Add a log entry to a step.
     */
    public function addStepLog(ProcessStep $step, string $level, string $message): void
    {
        $step->addLog($level, $message);
    }

    /**
     * Get the next pending step for a process.
     */
    public function getNextStep(InventoryProcess $process): ?ProcessStep
    {
        return $process->steps()
            ->where('status', StepStatus::PENDING)
            ->orderBy('step_order')
            ->first();
    }

    /**
     * Check if all steps are complete (or skipped).
     */
    public function areAllStepsComplete(InventoryProcess $process): bool
    {
        return $process->steps()
            ->whereNotIn('status', [StepStatus::COMPLETED, StepStatus::SKIPPED])
            ->doesntExist();
    }

    /**
     * Check if any step has failed.
     */
    public function hasFailedStep(InventoryProcess $process): bool
    {
        return $process->steps()
            ->where('status', StepStatus::FAILED)
            ->exists();
    }

    /**
     * Get process status summary.
     */
    public function getProcessStatus(InventoryProcess $process): array
    {
        $steps = $process->steps()->get();

        return [
            'processId' => $process->id,
            'status' => $process->status->value,
            'currentStep' => $process->current_step,
            'steps' => $steps->map(fn($step) => [
                'name' => $step->step_name,
                'status' => $step->status->value,
                'startedAt' => $step->started_at?->toIso8601String(),
                'completedAt' => $step->completed_at?->toIso8601String(),
                'duration' => $step->duration,
            ])->toArray(),
            'inventoryItemId' => $process->inventoryItem?->id,
            'startedAt' => $process->started_at?->toIso8601String(),
            'completedAt' => $process->completed_at?->toIso8601String(),
            'totalDuration' => $process->total_duration,
        ];
    }

    /**
     * Broadcast step status update.
     */
    protected function broadcastStepUpdate(ProcessStep $step): void
    {
        broadcast(new StepStatusUpdated($step))->toOthers();
    }
}
