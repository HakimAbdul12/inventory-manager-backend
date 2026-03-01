<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;
use Illuminate\Support\Facades\Http;
use App\Services\Chat\TelegramBotService;

class TelegramNgrokCommand extends Command
{
    protected $signature = 'telegram:ngrok {port=8000 : The port your app runs on}';

    protected $description = 'Start ngrok and automatically register the generated URL as your Telegram Webhook';

    public function handle(TelegramBotService $telegram)
    {
        $port = $this->argument('port');
        $this->info("Starting ngrok on port {$port}...");

        // Start ngrok in the background without the TUI
        $process = new Process(['ngrok', 'http', $port, '--log=stdout']);
        $process->setTimeout(null);
        $process->start();

        // Wait for tunnels to establish using a retry loop
        $this->info("Waiting for ngrok tunnel to be established...");

        $publicUrl = null;
        for ($i = 0; $i < 10; $i++) {
            sleep(2);

            if (!$process->isRunning()) {
                $this->error("ngrok failed to start or died unexpectedly. Is it installed?");
                return Command::FAILURE;
            }

            try {
                $response = Http::get('http://127.0.0.1:4040/api/tunnels');
                if ($response->successful()) {
                    $data = $response->json();

                    if (!empty($data['tunnels'])) {
                        foreach ($data['tunnels'] as $tunnel) {
                            if (isset($tunnel['public_url']) && str_starts_with($tunnel['public_url'], 'https')) {
                                $publicUrl = $tunnel['public_url'];
                                break 2;
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                // Keep waiting, ngrok API might not be responding yet
            }
        }

        if (!$publicUrl) {
            $this->error("Failed to extract HTTPS URL from ngrok after 20 seconds.");
            $process->stop();
            return Command::FAILURE;
        }

        $this->info("ngrok tunnel established at: <fg=green>{$publicUrl}</>");

        $this->info("Registering webhook with Telegram...");
        $webhookUrl = $publicUrl . '/webhooks/telegram';
        $secret = config('services.telegram.webhook_secret');

        if (empty($secret)) {
            $this->warn("⚠️ TELEGRAM_WEBHOOK_SECRET is not set in .env. Webhook will be registered without a secret token.");
        }

        if ($telegram->setWebhook($webhookUrl, $secret ?? '')) {
            $this->info("✅ Webhook successfully registered: <fg=cyan>{$webhookUrl}</>");
            $this->info("Press Ctrl+C to stop ngrok and keep the tunnel alive.");
        } else {
            $this->error("❌ Failed to register webhook with Telegram.");
        }

        // Keep the command running so ngrok stays alive
        $process->wait(function ($type, $buffer) {
            // Ignore standard output to avoid spam, but keep process open
        });

        return Command::SUCCESS;
    }
}
