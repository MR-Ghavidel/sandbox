<?php

namespace App\Support;

use App\Entities\AttendanceDayEntity;
use App\Entities\PayrollMonthEntity;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Calculates work hours, overtime/deduction and salary of a period.
 *
 * - Worked time is the sum of every complete arrive/leave pair, including work on days off.
 * - Required time is (work days so far) × (daily work hours); future work days are not required yet.
 * - Hourly rate = salary ÷ salary divisor days ÷ daily work hours.
 * - Overtime is paid at hourly rate × overtime multiplier, a deficit is deducted at the plain hourly rate, both by the exact minute.
 * - Insurance and tax are informational only and are NOT subtracted from the final amount.
 */
class PayrollCalculator
{
    /**
     * @param  Collection<int, AttendanceDayEntity>  $days  Every day of the period.
     */
    public function calculate(PayrollMonthEntity $settings, Collection $days, CarbonInterface $today): PayrollSummary
    {
        $workDays = $days->filter(fn (AttendanceDayEntity $day): bool => $day->isWorkDay);
        $countedWorkDays = $workDays->filter(fn (AttendanceDayEntity $day): bool => $day->date->lte($today) || $day->hasAnyTime());
        $remainingWorkDays = $workDays->count() - $countedWorkDays->count();

        $workedMinutes = $days->sum(fn (AttendanceDayEntity $day): int => $day->workedMinutes());
        $requiredMinutes = $countedWorkDays->count() * $settings->dailyWorkMinutes;
        $differenceMinutes = $workedMinutes - $requiredMinutes;

        $hourlyRate = $settings->salaryDivisorDays > 0 && $settings->dailyWorkMinutes > 0
            ? $settings->salary / $settings->salaryDivisorDays / ($settings->dailyWorkMinutes / 60)
            : 0.0;

        $overtimePay = $differenceMinutes > 0
            ? (int) round($differenceMinutes / 60 * $hourlyRate * $settings->overtimeMultiplier)
            : 0;
        $deduction = $differenceMinutes < 0
            ? (int) round(-$differenceMinutes / 60 * $hourlyRate)
            : 0;

        $grossAmount = $settings->salary + $overtimePay - $deduction;
        $taxableAmount = $settings->salary + $overtimePay;

        return new PayrollSummary(
            workedMinutes: $workedMinutes,
            countedWorkDays: $countedWorkDays->count(),
            totalWorkDays: $workDays->count(),
            remainingWorkDays: $remainingWorkDays,
            requiredMinutes: $requiredMinutes,
            differenceMinutes: $differenceMinutes,
            hourlyRate: $hourlyRate,
            overtimePay: $overtimePay,
            deduction: $deduction,
            grossAmount: $grossAmount,
            insurance: (int) round($taxableAmount * $settings->insuranceRatePercent / 100),
            tax: (int) round(max(0, $taxableAmount - $settings->taxExemption) * $settings->taxRatePercent / 100),
            advance: $settings->advance,
            finalAmount: $grossAmount - $settings->advance,
            averageDifferencePerDayMinutes: $countedWorkDays->isEmpty() ? null : intdiv($differenceMinutes, $countedWorkDays->count()),
            compensationPerRemainingDayMinutes: $differenceMinutes < 0 && $remainingWorkDays > 0
                ? (int) ceil(-$differenceMinutes / $remainingWorkDays)
                : null,
        );
    }
}
