<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ── SFTP Distribution Scheduler ─────────────────────────
Schedule::command('sftp:dispatch-scheduled-pushes --time=00:00')->dailyAt('00:00');
Schedule::command('sftp:dispatch-scheduled-pushes --time=12:00')->dailyAt('12:00');

// ── Email Leads Scheduler ───────────────────────────────
Schedule::command('leads:fetch-emails')->everyThirtyMinutes();

// ── Inactive Chats Scheduler ────────────────────────────
Schedule::command('chat:close-inactive')->everyMinute();
