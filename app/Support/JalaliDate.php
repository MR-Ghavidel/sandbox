<?php

namespace App\Support;

use DateTimeInterface;
use IntlDateFormatter;

class JalaliDate
{
    /**
     * Format a date in the Persian (Jalali) calendar using an ICU pattern.
     *
     * @see https://unicode-org.github.io/icu/userguide/format_parse/datetime/#date-field-symbol-table
     */
    public static function format(DateTimeInterface $date, string $pattern = 'd MMMM y'): string
    {
        $formatter = new IntlDateFormatter(
            'fa_IR@calendar=persian',
            IntlDateFormatter::NONE,
            IntlDateFormatter::NONE,
            $date->getTimezone(),
            IntlDateFormatter::TRADITIONAL,
            $pattern,
        );

        return $formatter->format($date);
    }
}
