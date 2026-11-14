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
     * Persian weekdays, starting with Saturday (the first day of the Persian week).
     *
     * @var list<string>
     */
    private const WEEKDAYS = ['شنبه', 'یکشنبه', 'دوشنبه', 'سه‌شنبه', 'چهارشنبه', 'پنجشنبه', 'جمعه'];

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
     * A Jalali date for a text input, with Latin digits: "1405/08/23".
     */
    public static function input(DateTimeInterface $date): string
    {
        $parts = self::parts($date);

        return sprintf('%04d/%02d/%02d', $parts['year'], $parts['month'], $parts['day']);
    }

    /**
     * Parse "1405/8/23" (Persian or Latin digits, / - or . separators) into a Gregorian date.
     */
    public static function parse(?string $value): ?CarbonImmutable
    {
        $value = TimeInput::latinDigits(trim((string) $value));

        if (! preg_match('~^(\d{3,4})[/\-.](\d{1,2})[/\-.](\d{1,2})$~', $value, $matches)) {
            return null;
        }

        return self::fromParts((int) $matches[1], (int) $matches[2], (int) $matches[3]);
    }

    /**
     * Build a Gregorian date from Jalali parts, or null when the parts are not a real date.
     */
    public static function fromParts(int|string $year, int|string $month, int|string $day): ?CarbonImmutable
    {
        $year = (int) TimeInput::latinDigits((string) $year);
        $month = (int) TimeInput::latinDigits((string) $month);
        $day = (int) TimeInput::latinDigits((string) $day);

        if ($year < 1000 || $year > 1600 || $month < 1 || $month > 12 || $day < 1 || $day > self::daysInMonth($year, $month)) {
            return null;
        }

        $date = self::toGregorian($year, $month, $day);

        return self::parts($date) === ['year' => $year, 'month' => $month, 'day' => $day] ? $date : null;
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

    /**
     * All Persian month names, keyed by month number (1–12).
     *
     * @return array<int, string>
     */
    public static function monthNames(): array
    {
        return array_combine(range(1, 12), array_map(self::monthName(...), range(1, 12)));
    }

    /**
     * Persian weekday names, keyed Saturday = 0 … Friday = 6.
     *
     * @return array<int, string>
     */
    public static function weekdayNames(): array
    {
        return self::WEEKDAYS;
    }

    public static function weekdayName(int $dayOfWeek): string
    {
        return self::WEEKDAYS[$dayOfWeek] ?? '';
    }

    /**
     * The weekday index (Saturday = 0 … Friday = 6) of a date.
     */
    public static function weekdayIndex(DateTimeInterface $date): int
    {
        return ((int) $date->format('w') + 1) % 7;
    }

    /**
     * A friendly Jalali date with the weekday: "شنبه ۲۳ آبان ۱۴۰۵".
     */
    public static function long(DateTimeInterface $date): string
    {
        return self::weekdayName(self::weekdayIndex($date)).' '.self::format($date, 'd MMMM y');
    }

    /**
     * The time part of a datetime, e.g. "08:30".
     */
    public static function time(DateTimeInterface $date): string
    {
        return $date->format('H:i');
    }

    private static function calendar(): IntlCalendar
    {
        $calendar = IntlCalendar::createInstance(date_default_timezone_get(), self::LOCALE);
        $calendar->clear();

        return $calendar;
    }
}
