<?php

namespace App\Console\Commands;

use App\Services\AlarmDispatcher;
use Illuminate\Console\Command;

class DispatchAlarms extends Command
{
    protected $signature = 'alarms:dispatch';

    protected $description = 'Send the class and assignment reminders that are due through the Bale bot';

    public function handle(AlarmDispatcher $dispatcher): int
    {
        $sent = $dispatcher->dispatchDue();

        $this->info("Sent {$sent} reminder(s).");

        return self::SUCCESS;
    }
}
