<?php

namespace App\Support;

/**
 * The result of calculating a month's work hours and salary. Amounts are rounded to whole units.
 */
final readonly class PayrollSummary
{
    public function __construct(
        public int $workedMinutes,
        public int $countedWorkDays,
        public int $totalWorkDays,
        public int $remainingWorkDays,
        public int $requiredMinutes,
        public int $differenceMinutes,
        public float $hourlyRate,
        public int $overtimePay,
        public int $deduction,
        public int $grossAmount,
        public int $insurance,
        public int $tax,
        public int $advance,
        public int $finalAmount,
        public ?int $averageDifferencePerDayMinutes,
        public ?int $compensationPerRemainingDayMinutes,
    ) {}

    public function hasOvertime(): bool
    {
        return $this->differenceMinutes > 0;
    }

    public function hasDeficit(): bool
    {
        return $this->differenceMinutes < 0;
    }
}
