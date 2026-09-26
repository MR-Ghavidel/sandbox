<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * A salary period for a Jalali month, following the company's work-hours reporting rule:
 * - months 1–6 run from the 27th of the previous month to the 26th (e.g. Mordad: 27 Tir → 26 Mordad),
 * - months 7–12 run from the 26th of the previous month to the 25th (e.g. Aban: 26 Mehr → 25 Aban).
 *
 * Where the rule switches, periods overlap or leave a gap: 26 Shahrivar belongs to both Shahrivar
 * (27 Mordad → 26 Shahrivar) and Mehr (26 Shahrivar → 25 Mehr), and 26 Esfand belongs to no period
 * (Esfand ends on the 25th, Farvardin starts on 27 Esfand).
 */
final readonly class PayrollPeriod
{
    /**
     * The Jalali day on which the given month's period closes.
     */
    public static function closingDay(int $month): int
    {
        return $month <= 6 ? 26 : 25;
    }

    /**
     * The Jalali day of the previous month on which the given month's period starts.
     */
    public static function startDay(int $month): int
    {
        return $month <= 6 ? 27 : 26;
    }

    private function __construct(
        public int $year,
        public int $month,
        public CarbonImmutable $startDate,
        public CarbonImmutable $endDate,
    ) {}

    /**
     * The period of the given Jalali year and month.
     */
    public static function for(int $year, int $month): self
    {
        [$previousYear, $previousMonth] = $month === 1 ? [$year - 1, 12] : [$year, $month - 1];

        return new self(
            year: $year,
            month: $month,
            startDate: JalaliDate::toGregorian($previousYear, $previousMonth, self::startDay($month)),
            endDate: JalaliDate::toGregorian($year, $month, self::closingDay($month)),
        );
    }

    /**
     * The period that the given day is reported in: its own month up to the closing day, the next month
     * after it. So 26 Shahrivar gives Shahrivar (it is also in Mehr), and 26 Esfand gives the coming Farvardin.
     */
    public static function containing(CarbonInterface $date): self
    {
        ['year' => $year, 'month' => $month, 'day' => $day] = JalaliDate::parts($date);

        if ($day <= self::closingDay($month)) {
            return self::for($year, $month);
        }

        return $month === 12 ? self::for($year + 1, 1) : self::for($year, $month + 1);
    }

    public function previous(): self
    {
        return $this->month === 1 ? self::for($this->year - 1, 12) : self::for($this->year, $this->month - 1);
    }

    public function next(): self
    {
        return $this->month === 12 ? self::for($this->year + 1, 1) : self::for($this->year, $this->month + 1);
    }

    /**
     * Every day of the period, in order.
     *
     * @return Collection<int, CarbonImmutable>
     */
    public function days(): Collection
    {
        return collect(range(0, (int) $this->startDate->diffInDays($this->endDate)))
            ->map(fn (int $offset): CarbonImmutable => $this->startDate->addDays($offset));
    }

    public function contains(CarbonInterface $date): bool
    {
        return $date->between($this->startDate->startOfDay(), $this->endDate->endOfDay());
    }

    /**
     * Human readable name, e.g. "مرداد ۱۴۰۵".
     */
    public function label(): string
    {
        return JalaliDate::monthName($this->month).' '.JalaliDate::format($this->endDate, 'y');
    }
}
