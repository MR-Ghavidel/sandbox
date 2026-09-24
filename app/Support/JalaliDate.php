<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use DateTimeInterface;
use IntlCalendar;
use IntlDateFormatter;

class JalaliDate
{
    private const LOCALE = 'fa_IR@calendar=persian';

    /**
     * Format a date in the Persian (Jalali) calendar using an ICU pattern.
     *
     * @see https://unicode-org.github.io/icu/userguide/format_parse/datetime/#date-field-symbol-table
     */
    public static function format(DateTimeInterface $date, string $pattern = 'd MMMM y'): string
    {
        $formatter = new IntlDateFormatter(
            self::LOCALE,
            IntlDateFormatter::NONE,
            IntlDateFormatter::NONE,
            $date->getTimezone(),
            IntlDateFormatter::TRADITIONAL,
            $pattern,
        );

        return $formatter->format($date);
    }

    /**
     * Convert a Jalali date to a Gregorian date (at the start of the day).
     */
    public static function toGregorian(int $year, int $month, int $day): CarbonImmutable
    {
        $calendar = self::calendar();
        $calendar->setDate($year, $month - 1, $day);

        return CarbonImmutable::createFromTimestamp(
            intdiv((int) $calendar->getTime(), 1000),
            date_default_timezone_get(),
        )->startOfDay();
    }

    /**
     * Split a date into its Jalali year, month and day.
     *
     * @return array{year: int, month: int, day: int}
     */
    public static function parts(DateTimeInterface $date): array
    {
        $calendar = self::calendar();
        $calendar->setTime($date->getTimestamp() * 1000);

        return [
            'year' => $calendar->get(IntlCalendar::FIELD_YEAR),
            'month' => $calendar->get(IntlCalendar::FIELD_MONTH) + 1,
            'day' => $calendar->get(IntlCalendar::FIELD_DAY_OF_MONTH),
        ];
    }

    /**
     * Get the number of days of a Jalali month (29–31).
     */
    public static function daysInMonth(int $year, int $month): int
    {
        $calendar = self::calendar();
        $calendar->setDate($year, $month - 1, 1);

        return $calendar->getActualMaximum(IntlCalendar::FIELD_DAY_OF_MONTH);
    }

    /**
     * Get the Persian name of a Jalali month, e.g. 5 => "مرداد".
     */
    public static function monthName(int $month): string
    {
        return self::format(self::toGregorian(1400, $month, 1), 'MMMM');
    }

    private static function calendar(): IntlCalendar
    {
        $calendar = IntlCalendar::createInstance(date_default_timezone_get(), self::LOCALE);
        $calendar->clear();

        return $calendar;
    }
}
