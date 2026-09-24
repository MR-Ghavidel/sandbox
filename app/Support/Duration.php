<?php

namespace App\Support;

use Illuminate\Support\Number;

class Duration
{
    /**
     * Format minutes as hours and minutes with Persian digits, e.g. 1453 => "۲۴:۱۳", -13 => "-۰:۱۳".
     */
    public static function format(int $minutes, bool $withPlusSign = false): string
    {
        $sign = match (true) {
            $minutes < 0 => '-',
            $minutes > 0 && $withPlusSign => '+',
            default => '',
        };

        $minutes = abs($minutes);
        $hours = Number::format(intdiv($minutes, 60), locale: 'fa');
        $remainder = Number::format($minutes % 60, locale: 'fa');

        return $sign.$hours.':'.(mb_strlen($remainder) === 1 ? '۰'.$remainder : $remainder);
    }
}
