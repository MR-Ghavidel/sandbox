<?php

namespace App\Support;

use App\Entities\AttendanceDayEntity;
use App\Repositories\AttendanceDayRepository;
use App\Repositories\PayrollMonthRepository;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Builds all-time statistics from every saved attendance day and the salary settings of each month.
 */
class WorkHistory
{
    public function __construct(
        private AttendanceDayRepository $attendanceDays,
        private PayrollMonthRepository $payrollMonths,
        private PayrollCalculator $calculator,
    ) {}

    /**
     * Returns null when no day with times has been recorded yet.
     */
    public function summarize(CarbonInterface $today): ?WorkHistorySummary
    {
        $savedDays = $this->attendanceDays->getAll();
        $attendedDays = $savedDays->filter(fn (AttendanceDayEntity $day): bool => $day->workedMinutes() > 0);

        if ($attendedDays->isEmpty()) {
            return null;
        }

        $firstDay = $attendedDays->first()->date;
        [$overtimeMinutes, $receivedAmount, $paidMonths] = $this->finishedPeriodTotals($savedDays, $firstDay, $today);

        $arrivals = $attendedDays->map(fn (AttendanceDayEntity $day): ?string => $this->firstArrival($day))->filter();
        $leaves = $attendedDays->map(fn (AttendanceDayEntity $day): ?string => $this->lastLeave($day))->filter();
        $longestDay = $attendedDays->sortByDesc(fn (AttendanceDayEntity $day): int => $day->workedMinutes())->first();

        return new WorkHistorySummary(
            firstDay: $firstDay,
            tenure: $firstDay->diff($today),
            attendedDays: $attendedDays->count(),
            workedMinutes: $attendedDays->sum(fn (AttendanceDayEntity $day): int => $day->workedMinutes()),
            overtimeMinutes: $overtimeMinutes,
            receivedAmount: $receivedAmount,
            paidMonths: $paidMonths,
            averageArrivalMinutes: $arrivals->isEmpty() ? null : (int) round($arrivals->avg(fn (string $time): int => AttendanceDayEntity::toMinutes($time))),
            earliestArrival: $this->extreme($arrivals, fn (string $time): int => AttendanceDayEntity::toMinutes($time)),
            latestLeave: $this->extreme($leaves, fn (string $time): int => -AttendanceDayEntity::toMinutes($time)),
            longestDay: ['date' => $longestDay->date, 'minutes' => $longestDay->workedMinutes()],
        );
    }

    /**
     * Net overtime, received amount and number of paid months, over periods that have already ended.
     * The amount only counts months whose salary settings were saved. Only recorded days are used:
     * a day that was never saved (e.g. before the first Bizagi import) is unknown, not a day of absence.
     *
     * @param  Collection<string, AttendanceDayEntity>  $savedDays
     * @return array{0: int, 1: int, 2: int}
     */
    private function finishedPeriodTotals(Collection $savedDays, CarbonImmutable $firstDay, CarbonInterface $today): array
    {
        $overtimeMinutes = 0;
        $receivedAmount = 0;
        $paidMonths = 0;

        for ($period = PayrollPeriod::containing($firstDay); $period->endDate->lt($today->copy()->startOfDay()); $period = $period->next()) {
            $days = $period->days()->map(fn (CarbonImmutable $date): ?AttendanceDayEntity => $savedDays->get($date->toDateString()))->filter()->values();
            $savedSettings = $this->payrollMonths->find($period->year, $period->month);
            $summary = $this->calculator->calculate($savedSettings ?? $this->payrollMonths->findOrDefaults($period->year, $period->month), $days, $today);

            $overtimeMinutes += $summary->differenceMinutes;

            if ($savedSettings !== null) {
                $receivedAmount += $summary->finalAmount;
                $paidMonths++;
            }
        }

        return [$overtimeMinutes, $receivedAmount, $paidMonths];
    }

    private function firstArrival(AttendanceDayEntity $day): ?string
    {
        foreach ($day->pairs as $pair) {
            if ($pair['arrive'] !== null && $pair['leave'] !== null) {
                return $pair['arrive'];
            }
        }

        return null;
    }

    private function lastLeave(AttendanceDayEntity $day): ?string
    {
        foreach (array_reverse($day->pairs) as $pair) {
            if ($pair['arrive'] !== null && $pair['leave'] !== null) {
                return $pair['leave'];
            }
        }

        return null;
    }

    /**
     * The day whose time has the lowest sort value (use a negative value for "latest").
     *
     * @param  Collection<string, string>  $timesByDate
     * @return array{date: CarbonImmutable, time: string}|null
     */
    private function extreme(Collection $timesByDate, callable $sortValue): ?array
    {
        if ($timesByDate->isEmpty()) {
            return null;
        }

        $date = $timesByDate->sortBy($sortValue)->keys()->first();

        return ['date' => CarbonImmutable::parse($date), 'time' => $timesByDate[$date]];
    }
}
