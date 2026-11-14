<?php

namespace App\Console\Commands;

use App\Services\Bale\BaleBot;
use App\Services\Bale\BaleClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Local development alternative to a webhook: ask Bale for new updates in a loop.
 */
class BalePoll extends Command
{
    protected $signature = 'bale:poll {--once : Fetch one batch of updates and exit}';

    protected $description = 'Receive Bale bot updates by long polling (use this instead of a webhook locally)';

    public function handle(BaleClient $client, BaleBot $bot): int
    {
        if (! $client->configured()) {
            $this->error('BALE_BOT_TOKEN is not set.');

            return self::FAILURE;
        }

        $this->info('Listening for Bale updates. Press Ctrl+C to stop.');

        do {
            $offset = (int) Cache::get('bale:update-offset', 0);
            $updates = $client->getUpdates($offset);

            foreach ($updates as $update) {
                $updateId = (int) ($update['update_id'] ?? 0);
                Cache::forever('bale:update-offset', $updateId + 1);

                try {
                    $bot->handleUpdate($update);
                } catch (Throwable $exception) {
                    Log::error('Bale update failed.', [
                        'update_id' => $updateId,
                        'message' => $exception->getMessage(),
                    ]);
                    $this->warn("Update {$updateId} failed: {$exception->getMessage()}");
                }
            }

            if ($this->option('once')) {
                break;
            }

            if ($updates === []) {
                usleep(500_000);
            }
        } while (true);

        return self::SUCCESS;
    }
}
