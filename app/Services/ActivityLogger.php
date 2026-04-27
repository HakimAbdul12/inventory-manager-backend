<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;

class ActivityLogger
{
    private ?User $causer = null;
    private ?Model $subject = null;
    private array $properties = [];
    private ?string $description = null;

    /**
     * Set the user who caused the activity.
     * If not set, auto-resolves from the current request.
     */
    public function causedBy(?User $user): static
    {
        $this->causer = $user;
        return $this;
    }

    /**
     * Set the subject (entity) this activity relates to.
     */
    public function on(Model $subject): static
    {
        $this->subject = $subject;
        return $this;
    }

    /**
     * Attach metadata (e.g., before/after state) to the log entry.
     */
    public function withProperties(array $properties): static
    {
        $this->properties = $properties;
        return $this;
    }

    /**
     * Set a human-readable description.
     */
    public function withDescription(string $description): static
    {
        $this->description = $description;
        return $this;
    }

    /**
     * Record the activity log entry.
     */
    public function log(string $action): ActivityLog
    {
        $user = $this->causer ?? Request::user();
        $tenant = app()->bound('current_tenant') ? app('current_tenant') : null;

        $log = ActivityLog::withoutGlobalScope('tenant')->create([
            'tenant_id' => $tenant?->id,
            'user_id' => $user?->id,
            'action' => $action,
            'subject_type' => $this->subject ? $this->subject->getMorphClass() : null,
            'subject_id' => $this->subject ? $this->subject->getKey() : null,
            'description' => $this->description,
            'properties' => !empty($this->properties) ? $this->properties : null,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);

        // Reset state for next use
        $this->reset();

        return $log;
    }

    /**
     * Convenience: log with subject and properties in one call.
     */
    public static function record(
        string $action,
        ?Model $subject = null,
        ?string $description = null,
        array $properties = [],
    ): ActivityLog {
        $logger = new static();

        if ($subject) {
            $logger->on($subject);
        }
        if ($description) {
            $logger->withDescription($description);
        }
        if (!empty($properties)) {
            $logger->withProperties($properties);
        }

        return $logger->log($action);
    }

    /**
     * Build a diff of old and new values, filtering out unchanged fields.
     */
    public static function diff(array $old, array $new): array
    {
        $changes = [];

        foreach ($new as $key => $newValue) {
            $oldValue = $old[$key] ?? null;

            // Normalize for comparison (cast numbers to strings for loose equality)
            if ($oldValue != $newValue) {
                $changes[$key] = [
                    'old' => $oldValue,
                    'new' => $newValue,
                ];
            }
        }

        return $changes;
    }

    private function reset(): void
    {
        $this->causer = null;
        $this->subject = null;
        $this->properties = [];
        $this->description = null;
    }
}
