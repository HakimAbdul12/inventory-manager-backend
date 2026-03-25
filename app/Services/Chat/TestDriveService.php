<?php

namespace App\Services\Chat;

use App\Models\TestDrive;
use App\Models\TestDriveConfig;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class TestDriveService
{
    /**
     * Get available test drive slots for a given date range.
     */
    public function getAvailableSlots(string $tenantId, ?string $fromDate = null, int $days = 7): array
    {
        $config = TestDriveConfig::where('tenant_id', $tenantId)->first();
        if (!$config || !$config->is_active) {
            return [];
        }

        $startDate = $fromDate ? Carbon::parse($fromDate) : Carbon::today();
        $endDate = $startDate->copy()->addDays($days - 1);

        $period = CarbonPeriod::create($startDate, $endDate);
        $slots = [];

        foreach ($period as $date) {
            $dateStr = $date->toDateString();
            $dayOfWeek = (int) $date->dayOfWeek;

            // Skip unavailable days
            if (!$config->isDayAvailable($dayOfWeek)) {
                continue;
            }

            // Skip blocked dates
            if ($config->isDateBlocked($dateStr)) {
                continue;
            }

            // Skip past dates
            if ($date->lt(Carbon::today())) {
                continue;
            }

            $daySlots = $this->generateDaySlots($config, $tenantId, $dateStr);
            if (!empty($daySlots)) {
                $slots[] = [
                    'date' => $dateStr,
                    'day_name' => $date->format('l'),
                    'slots' => $daySlots,
                ];
            }
        }

        return $slots;
    }

    /**
     * Generate available time slots for a specific day.
     */
    protected function generateDaySlots(TestDriveConfig $config, string $tenantId, string $date): array
    {
        $startTime = Carbon::parse($date . ' ' . $config->start_time);
        $endTime = Carbon::parse($date . ' ' . $config->end_time);
        $duration = $config->duration_minutes;
        $buffer = $config->buffer_minutes;
        $slotStep = $duration + $buffer;

        // Get existing active bookings for this date
        $existingBookings = TestDrive::where('tenant_id', $tenantId)
            ->forDate($date)
            ->active()
            ->get();

        // Count total active bookings for max_per_day check
        $dailyBookingCount = $existingBookings->count();

        $slots = [];
        $current = $startTime->copy();
        $now = Carbon::now();

        while ($current->copy()->addMinutes($duration)->lte($endTime)) {
            $slotStart = $current->format('H:i');
            $slotEnd = $current->copy()->addMinutes($duration)->format('H:i');

            // Determine availability
            $isAvailable = true;
            $reason = null;

            // Skip slots in the past for today
            if (Carbon::parse($date)->isToday() && $current->lt($now)) {
                $isAvailable = false;
                $reason = 'past';
            }

            // Check max per day
            if ($isAvailable && $config->max_per_day !== null && $dailyBookingCount >= $config->max_per_day) {
                $isAvailable = false;
                $reason = 'daily_limit';
            }

            // Check concurrency — how many bookings overlap this time slot
            if ($isAvailable) {
                $overlapping = $existingBookings->filter(function ($booking) use ($slotStart, $slotEnd) {
                    return $booking->scheduled_time < $slotEnd && $booking->end_time > $slotStart;
                })->count();

                if ($overlapping >= $config->max_concurrent) {
                    $isAvailable = false;
                    $reason = 'slot_full';
                }
            }

            $slots[] = [
                'time' => $slotStart,
                'end_time' => $slotEnd,
                'available' => $isAvailable,
            ];

            $current->addMinutes($slotStep);
        }

        return $slots;
    }

    /**
     * Book a test drive.
     */
    public function bookTestDrive(string $tenantId, array $data): TestDrive
    {
        $config = TestDriveConfig::where('tenant_id', $tenantId)->first();
        if (!$config || !$config->is_active) {
            throw new \Exception('Test drive scheduling is not available.');
        }

        $date = $data['date'];
        $time = $data['time'];
        $endTime = Carbon::parse($date . ' ' . $time)
            ->addMinutes($config->duration_minutes)
            ->format('H:i');

        // Validate the slot is actually available
        if (!$this->isSlotAvailable($config, $tenantId, $date, $time, $endTime)) {
            throw new \Exception('This time slot is no longer available. Please choose another.');
        }

        $testDrive = TestDrive::create([
            'tenant_id' => $tenantId,
            'conversation_id' => $data['conversation_id'] ?? null,
            'vehicle_id' => $data['vehicle_id'] ?? null,
            'visitor_name' => $data['name'] ?? null,
            'visitor_email' => $data['email'] ?? null,
            'visitor_phone' => $data['phone'] ?? null,
            'scheduled_date' => $date,
            'scheduled_time' => $time,
            'end_time' => $endTime,
            'status' => TestDrive::STATUS_PENDING,
            'notes' => $data['notes'] ?? null,
        ]);

        if ($testDrive->visitor_email) {
            $this->sendConfirmationEmail($tenantId, $testDrive);
        }

        return $testDrive;
    }

    /**
     * Send confirmation email using Tenant's configured outbound mailer.
     */
    protected function sendConfirmationEmail(string $tenantId, TestDrive $testDrive): void
    {
        $tenantSettings = \App\Models\TenantEmailSetting::where('tenant_id', $tenantId)->first();
        
        $mailer = config('mail.default');
        $fromAddress = config('mail.from.address');
        $fromName = $testDrive->tenant->name ?? config('app.name');

        if ($tenantSettings && $tenantSettings->mail_mailer) {
            \Illuminate\Support\Facades\Config::set('mail.mailers.tenant', [
                'transport' => $tenantSettings->mail_mailer,
                'host' => $tenantSettings->mail_host,
                'port' => $tenantSettings->mail_port,
                'encryption' => $tenantSettings->mail_encryption,
                'username' => $tenantSettings->mail_username,
                'password' => $tenantSettings->mail_password,
                'timeout' => null,
            ]);
            $mailer = 'tenant';

            $fromAddress = $tenantSettings->mail_from_address ?? $fromAddress;
            $fromName = $tenantSettings->mail_from_name ?? $fromName;
        }

        try {
            \Illuminate\Support\Facades\Mail::mailer($mailer)
                ->to($testDrive->visitor_email)
                ->send(
                    (new \App\Mail\TestDriveConfirmationMail($testDrive))
                        ->from($fromAddress, $fromName)
                );
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to send test drive confirmation email: ' . $e->getMessage());
        }
    }

    /**
     * Reschedule a test drive by booking code.
     */
    public function rescheduleTestDrive(string $bookingCode, string $newDate, string $newTime): TestDrive
    {
        $testDrive = TestDrive::where('booking_code', $bookingCode)->firstOrFail();

        if (!$testDrive->isModifiable()) {
            throw new \Exception('This test drive cannot be rescheduled.');
        }

        $config = TestDriveConfig::where('tenant_id', $testDrive->tenant_id)->first();
        if (!$config) {
            throw new \Exception('Test drive configuration not found.');
        }

        $endTime = Carbon::parse($newDate . ' ' . $newTime)
            ->addMinutes($config->duration_minutes)
            ->format('H:i');

        if (!$this->isSlotAvailable($config, $testDrive->tenant_id, $newDate, $newTime, $endTime, $testDrive->id)) {
            throw new \Exception('The new time slot is not available.');
        }

        $testDrive->update([
            'scheduled_date' => $newDate,
            'scheduled_time' => $newTime,
            'end_time' => $endTime,
        ]);

        return $testDrive->fresh();
    }

    /**
     * Cancel a test drive by booking code.
     */
    public function cancelTestDrive(string $bookingCode, ?string $reason = null): TestDrive
    {
        $testDrive = TestDrive::where('booking_code', $bookingCode)->firstOrFail();

        if (!$testDrive->isModifiable()) {
            throw new \Exception('This test drive cannot be cancelled.');
        }

        $testDrive->update([
            'status' => TestDrive::STATUS_CANCELLED,
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
        ]);

        return $testDrive->fresh();
    }

    /**
     * Look up a test drive by booking code.
     */
    public function lookupTestDrive(string $bookingCode): ?TestDrive
    {
        return TestDrive::where('booking_code', $bookingCode)
            ->with('vehicle')
            ->first();
    }

    /**
     * Check if a specific slot is available.
     */
    protected function isSlotAvailable(
        TestDriveConfig $config,
        string $tenantId,
        string $date,
        string $time,
        string $endTime,
        ?string $excludeId = null
    ): bool {
        $dateCarbon = Carbon::parse($date);
        $dayOfWeek = (int) $dateCarbon->dayOfWeek;

        if (!$config->isDayAvailable($dayOfWeek)) {
            return false;
        }

        if ($config->isDateBlocked($date)) {
            return false;
        }

        if ($dateCarbon->lt(Carbon::today())) {
            return false;
        }

        if ($dateCarbon->isToday() && Carbon::parse($date . ' ' . $time)->lt(Carbon::now())) {
            return false;
        }

        $query = TestDrive::where('tenant_id', $tenantId)
            ->forDate($date)
            ->active();

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        $existingBookings = $query->get();

        // Check max per day
        if ($config->max_per_day !== null && $existingBookings->count() >= $config->max_per_day) {
            return false;
        }

        // Check concurrency at specific time
        $overlapping = $existingBookings->filter(function ($booking) use ($time, $endTime) {
            return $booking->scheduled_time < $endTime && $booking->end_time > $time;
        })->count();

        return $overlapping < $config->max_concurrent;
    }
}
