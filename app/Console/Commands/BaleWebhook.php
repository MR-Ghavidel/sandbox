<?php

namespace App\Console\Commands;

use App\Services\Bale\BaleClient;
use Illuminate\Console\Command;

class BaleWebhook extends Command
{
    protected $signature = 'bale:webhook {url? : Public webhook URL} {--delete : Remove the webhook instead}';

    protected $description = 'Register (or remove) the Bale bot webhook';

    public function handle(BaleClient $client): int
    {
        if (! $client->configured()) {
            $this->error('BALE_BOT_TOKEN is not set.');

            return self::FAILURE;
        }

        if ($this->option('delete')) {
            $client->setWebhook(null);
            $this->info('Webhook removed. You can use `php artisan bale:poll` now.');

            return self::SUCCESS;
        }

        $url = $this->argument('url') ?: url('/bale/webhook/'.config('bale.webhook_secret'));

        $response = $client->setWebhook(rtrim((string) $url, '/'));

        if ($response === null || ($response['ok'] ?? false) !== true) {
            $this->error('Bale rejected the webhook. Check the URL and the bot token.');

            return self::FAILURE;
        }

        $this->info('Webhook set to '.$url);

        return self::SUCCESS;
    }
}
