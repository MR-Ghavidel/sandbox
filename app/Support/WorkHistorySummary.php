<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use DateInterval;

/**
 * Fun all-time numbers about the recorded work history, shown on the home page.
 */
final readonly class WorkHistorySummary
{
    /**
     * @param  array{date: CarbonImmutable, time: string}|null  $earliestArrival
     * @param  array{date: CarbonImmutable, time: string}|null  $latestLeave
     * @param  array{date: CarbonImmutable, minutes: int}|null  $longestDay
     */
    public function __construct(
        public CarbonImmutable $firstDay,
        public DateInterval $tenure,
        public int $attendedDays,
        public int $workedMinutes,
        public int $overtimeMinutes,
        public int $receivedAmount,
        public int $paidMonths,
        public ?int $averageArrivalMinutes,
        public ?array $earliestArrival,
        public ?array $latestLeave,
        public ?array $longestDay,
    ) {}

    /**
     * Worked time as full 24-hour days, e.g. "like 52 days and nights non-stop".
     */
    public function workedFullDays(): float
    {
        return $this->workedMinutes / (24 * 60);
    }

    /**
     * How many times the extended Lord of the Rings trilogy (about 686 minutes) fits into the worked time.
     */
    public function lordOfTheRingsMarathons(): int
    {
        return intdiv($this->workedMinutes, 686);
    }
}
