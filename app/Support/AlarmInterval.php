<?php

namespace App\Support;

use Illuminate\Support\Number;

/**
 * Turns "minutes before" into the Persian label shown on the website and in the bot.
 */
class AlarmInterval
{
    /**
     * Common choices offered as hints in the alarm form (1 week down to 5 minutes).
     *
     * @var list<int>
     */
    public const PRESETS = [10080, 2880, 1440, 720, 180, 120, 60, 30, 15, 10, 5];

    public static function label(int $minutes): string
    {
        if ($minutes <= 0) {
            return 'در لحظه شروع';
        }

        $days = intdiv($minutes, 1440);
        $hours = intdiv($minutes % 1440, 60);
        $remainingMinutes = $minutes % 60;

        $parts = [];

        if ($days > 0) {
            $parts[] = Number::format($days, locale: 'fa').' روز';
        }

        if ($hours > 0) {
            $parts[] = Number::format($hours, locale: 'fa').' ساعت';
        }

        if ($remainingMinutes > 0) {
            $parts[] = Number::format($remainingMinutes, locale: 'fa').' دقیقه';
        }

        return implode(' و ', $parts).' قبل';
    }
}
