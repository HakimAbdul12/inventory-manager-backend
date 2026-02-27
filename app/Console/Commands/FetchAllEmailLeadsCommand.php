<?php

namespace App\Console\Commands;

use App\Models\TenantEmailSetting;
use App\Jobs\FetchEmailLeadsJob;
use Illuminate\Console\Command;

class FetchAllEmailLeadsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'leads:fetch-emails';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dispatches FetchEmailLeadsJob for all active tenant email settings';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting to dispatch email fetch jobs...');

        $settings = TenantEmailSetting::where('is_active', true)->get();

        $count = 0;
        foreach ($settings as $setting) {
            if (!empty($setting->imap_password)) {
                FetchEmailLeadsJob::dispatch($setting);
                $count++;
            }
        }

        $this->info("Dispatched {$count} email fetch jobs.");
    }
}
