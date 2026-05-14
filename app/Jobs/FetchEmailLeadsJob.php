<?php

namespace App\Jobs;

use App\Models\Lead;
use App\Models\TenantEmailSetting;
use App\Services\AdfParserService;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Webklex\PHPIMAP\ClientManager;

class FetchEmailLeadsJob implements ShouldQueue
{
    use Queueable;

    public $timeout = 300; // 5 minutes max

    protected TenantEmailSetting $setting;

    /**
     * Create a new job instance.
     */
    public function __construct(TenantEmailSetting $setting)
    {
        $this->setting = $setting;
    }

    /**
     * Execute the job.
     */
    public function handle(AdfParserService $adfParser): void
    {
        if (!$this->setting->is_active || empty($this->setting->imap_password)) {
            Log::info("FetchEmailLeadsJob: Skipped for Tenant {$this->setting->tenant_id} as settings are inactive or missing password.");
            return;
        }

        try {
            $cm = new ClientManager();
            $client = $cm->make([
                'host'          => $this->setting->imap_host,
                'port'          => $this->setting->imap_port,
                'encryption'    => $this->setting->imap_encryption !== false ? $this->setting->imap_encryption : false,
                'validate_cert' => true,
                'username'      => $this->setting->imap_username,
                'password'      => $this->setting->imap_password,
                'protocol'      => 'imap',
            ]);

            $client->connect();

            // Fetch INBOX folder
            $folder = $client->getFolder('INBOX');

            // Get unseen emails with a hard limit to avoid memory exhaustion
            // Since this job runs frequently, pulling 10-20 at a time prevents memory spikes.
            $messages = $folder->query()->unseen()->limit(10, 1)->get();

            Log::info("FetchEmailLeadsJob: Found {$messages->count()} new unseen emails for Tenant {$this->setting->tenant_id}.");

            foreach ($messages as $message) {
                // Determine external reference id (Message-ID header usually)
                $externalRefId = $message->getMessageId();

                // Check if this lead was already processed
                if (
                    $externalRefId && Lead::withoutGlobalScope('tenant')
                    ->where('tenant_id', $this->setting->tenant_id)
                    ->where('external_reference_id', $externalRefId)
                    ->exists()
                ) {
                    $message->setFlag(['Seen']);
                    continue;
                }

                $content = $message->getTextBody() ?? $message->getHTMLBody();

                // Parse ADF xml from the email body
                $parsedLead = $adfParser->parse($content);

                if ($parsedLead) {
                    $nameParts = explode(' ', $parsedLead['name'] ?? '', 2);
                    Lead::withoutGlobalScope('tenant')->create([
                        'tenant_id' => $this->setting->tenant_id,
                        'first_name' => $nameParts[0] ?? null,
                        'last_name' => $nameParts[1] ?? null,
                        'email' => $parsedLead['email'],
                        'phone' => $parsedLead['phone'],
                        'notes' => $parsedLead['notes'],
                        'source' => 'email',
                        'source_type' => Lead::SOURCE_WEBSITE,
                        'source_name' => $parsedLead['provider_name'] ?? 'Email Lead',
                        'recorded_by_type' => Lead::RECORDED_BY_SYSTEM,
                        'provider_name' => $parsedLead['provider_name'] ?? null,
                        'vehicle_details' => $parsedLead['vehicle_details'] ?? null,
                        'status' => Lead::STATUS_NEW,
                        'external_reference_id' => $externalRefId,
                        'last_activity_at' => now(),
                    ]);
                } else {
                    Log::warning("FetchEmailLeadsJob: Failed to parse ADF from message ID {$externalRefId} for Tenant {$this->setting->tenant_id}.");
                }

                // Mark as seen so we don't process it again
                $message->setFlag(['Seen']);
            }

            $client->disconnect();
        } catch (Exception $e) {
            Log::error("FetchEmailLeadsJob: Error syncing emails for Tenant {$this->setting->tenant_id}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
