<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Class and assignment reminders are sent by the Bale bot. Run `php artisan schedule:work` locally.
Schedule::command('alarms:dispatch')->everyMinute()->withoutOverlapping(5);
