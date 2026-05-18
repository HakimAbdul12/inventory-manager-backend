<?php

namespace App\Console\Commands;

use App\Models\Lead;
use App\Models\Prospect;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateProspectsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'crm:migrate-prospects';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create prospects for existing leads based on email/phone';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting prospect migration...');

        $leads = Lead::whereNull('prospect_id')->get();
        $count = $leads->count();

        if ($count === 0) {
            $this->info('No leads without a prospect found.');
            return;
        }

        $this->info("Found {$count} leads to process.");

        $bar = $this->output->createProgressBar($count);
        $bar->start();

        DB::beginTransaction();

        try {
            foreach ($leads as $lead) {
                // Try to find an existing prospect for this tenant by email or phone
                $prospect = null;
                
                if ($lead->email) {
                    $prospect = Prospect::where('tenant_id', $lead->tenant_id)
                        ->where('email', $lead->email)
                        ->first();
                }
                
                if (!$prospect && $lead->phone) {
                    $prospect = Prospect::where('tenant_id', $lead->tenant_id)
                        ->where('phone', $lead->phone)
                        ->first();
                }

                // If no prospect exists, create one
                if (!$prospect) {
                    $prospect = Prospect::create([
                        'tenant_id' => $lead->tenant_id,
                        'first_name' => $lead->first_name,
                        'last_name' => $lead->last_name,
                        'email' => $lead->email,
                        'phone' => $lead->phone,
                        'assigned_to' => $lead->assigned_to,
                    ]);
                }

                // Update the lead with the prospect_id
                $lead->update(['prospect_id' => $prospect->id]);

                $bar->advance();
            }

            DB::commit();
            $bar->finish();
            $this->newLine();
            $this->info('Migration completed successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Migration failed: ' . $e->getMessage());
        }
    }
}
