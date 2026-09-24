<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * A salary period for a Jalali month: from the day after the previous month's
 * closing day up to and including this month's closing day (27th → 26th).
 */
final readonly class PayrollPeriod
{
    /**
     * The Jalali day of month on which every period closes.
     */
    public const CLOSING_DAY = 26;

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
            startDate: JalaliDate::toGregorian($previousYear, $previousMonth, self::CLOSING_DAY)->addDay(),
            endDate: JalaliDate::toGregorian($year, $month, self::CLOSING_DAY),
        );
    }

    /**
     * The period that the given day belongs to.
     */
    public static function containing(CarbonInterface $date): self
    {
        ['year' => $year, 'month' => $month, 'day' => $day] = JalaliDate::parts($date);

        if ($day <= self::CLOSING_DAY) {
            return self::for($year, $month);
        }

        return $month === 12 ? self::for($year + 1, 1) : self::for($year, $month + 1);
    }

    public function previous(): self
    {
        return self::containing($this->startDate->subDay());
    }

    public function next(): self
    {
        return self::containing($this->endDate->addDay());
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
