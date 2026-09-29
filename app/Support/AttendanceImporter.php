<?php

namespace App\Support;

use App\Entities\AttendanceDayEntity;
use App\Entities\AttendanceImportEntity;
use App\Entities\PayrollMonthEntity;
use App\Repositories\AttendanceDayRepository;
use App\Repositories\AttendanceImportRepository;
use App\Repositories\PayrollMonthRepository;
use Carbon\CarbonImmutable;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Compares days received from Bizagi or from Excel sheets with the saved days and applies them.
 *
 * Merge rules for each received date:
 * - Received times replace the saved times. A date without any received time keeps its saved times.
 * - Excel days bring their own "work day" flag and note, which replace the saved ones.
 * - A holiday in Bizagi (e.g. "تعطیلی جمعه") makes the day a non-work day. Otherwise the saved
 *   "work day" flag and note are kept, so manual choices (leave, official holidays) survive.
 * - Hourly leave in Bizagi becomes the day's note. Paid leave also becomes a time pair, because
 *   Bizagi counts it as worked time; unpaid leave ("بدون حقوق") does not.
 * - Monthly settings from Excel are saved only for months that have no saved settings yet.
 */
class AttendanceImporter
{
    public const STATUS_NEW = 'new';

    public const STATUS_CHANGED = 'changed';

    public const STATUS_UNCHANGED = 'unchanged';

    public const STATUS_EMPTY = 'empty';

    public function __construct(
        private AttendanceDayRepository $attendanceDays,
        private AttendanceImportRepository $imports,
        private PayrollMonthRepository $payrollMonths,
        private ExcelAttendanceReader $excelReader,
    ) {}

    /**
     * @return Collection<int, array{date: CarbonImmutable, period: PayrollPeriod, current: ?AttendanceDayEntity, merged: AttendanceDayEntity, status: string}>
     */
    public function preview(AttendanceImportEntity $import): Collection
    {
        $incomingDays = collect($import->days)->sortBy('date')->values();

        if ($incomingDays->isEmpty()) {
            return collect();
        }

        $savedDays = $this->attendanceDays->getBetween(
            CarbonImmutable::parse($incomingDays->first()['date']),
            CarbonImmutable::parse($incomingDays->last()['date']),
        );

        return $incomingDays->map(function (array $incoming) use ($savedDays): array {
            $date = CarbonImmutable::parse($incoming['date']);
            $current = $savedDays->get($date->toDateString());
            $merged = $this->merge($date, $incoming, $current);

            return [
                'date' => $date,
                'period' => PayrollPeriod::containing($date),
                'current' => $current,
                'merged' => $merged,
                'status' => $this->status($incoming, $current, $merged),
            ];
        });
    }

    /**
     * Read monthly Excel sheets and store them as one import waiting to be reviewed.
     *
     * @param  array<string, string>  $paths  File name => path on disk.
     *
     * @throws InvalidArgumentException When a file cannot be read; the message names the file.
     */
    public function createFromExcel(array $paths): int
    {
        $sheets = [];

        foreach ($paths as $name => $path) {
            try {
                $sheets[] = $this->excelReader->read($path);
            } catch (InvalidArgumentException|Exception $exception) {
                throw new InvalidArgumentException("«{$name}»: {$exception->getMessage()}", previous: $exception);
            }
        }

        $merged = $this->excelReader->merge($sheets);

        return $this->imports->create('excel', $merged['days'], $merged['payroll_months']);
    }

    /**
     * Save the merged days and mark the import as applied.
     */
    public function apply(AttendanceImportEntity $import): void
    {
        DB::transaction(function () use ($import): void {
            $this->attendanceDays->saveMany(
                $this->preview($import)
                    ->whereIn('status', [self::STATUS_NEW, self::STATUS_CHANGED])
                    ->pluck('merged'),
            );

            foreach ($this->payrollMonthsPreview($import) as $month) {
                if ($month['is_new']) {
                    $this->payrollMonths->save($month['settings']);
                }
            }

            $this->imports->markApplied($import->id);
        });
    }

    /**
     * Monthly settings carried by the import (Excel only). Months that already have saved settings keep them.
     *
     * @return Collection<int, array{period: PayrollPeriod, settings: PayrollMonthEntity, is_new: bool}>
     */
    public function payrollMonthsPreview(AttendanceImportEntity $import): Collection
    {
        return collect($import->payrollMonths)->map(function (array $month): array {
            $saved = $this->payrollMonths->find($month['year'], $month['month']);
            $defaults = PayrollMonthEntity::defaultsFor($month['year'], $month['month'], $this->payrollMonths->latestBefore($month['year'], $month['month']));

            return [
                'period' => PayrollPeriod::for($month['year'], $month['month']),
                'settings' => $saved ?? new PayrollMonthEntity(
                    id: null,
                    year: $month['year'],
                    month: $month['month'],
                    salary: $month['salary'],
                    dailyWorkMinutes: $month['daily_work_minutes'],
                    salaryDivisorDays: $month['salary_divisor_days'],
                    overtimeMultiplier: $defaults->overtimeMultiplier,
                    insuranceRatePercent: $defaults->insuranceRatePercent,
                    taxRatePercent: $defaults->taxRatePercent,
                    taxExemption: $defaults->taxExemption,
                    advance: $month['advance'],
                ),
                'is_new' => $saved === null,
            ];
        });
    }

    /**
     * @param  array{date: string, pairs: list<array{arrive: ?string, leave: ?string}>, leaves?: list<array{label: string, from: string, to: string}>, holiday_label: ?string, is_work_day?: bool, note?: ?string}  $incoming
     */
    private function merge(CarbonImmutable $date, array $incoming, ?AttendanceDayEntity $current): AttendanceDayEntity
    {
        $base = $current ?? AttendanceDayEntity::blank($date);
        $leaves = collect($incoming['leaves'] ?? []);
        // Pairs are rebuilt key by key: MySQL's JSON column reorders object keys, which would make
        // identical pairs fail the strict comparison in status().
        $incomingPairs = array_map(
            fn (array $pair): array => ['arrive' => $pair['arrive'] ?? null, 'leave' => $pair['leave'] ?? null],
            array_pad(array_slice($this->withPaidLeaves($incoming['pairs'], $leaves), 0, AttendanceDayEntity::PAIRS_PER_DAY), AttendanceDayEntity::PAIRS_PER_DAY, []),
        );
        $hasIncomingTimes = collect($incomingPairs)->contains(fn (array $pair): bool => $pair['arrive'] !== null || $pair['leave'] !== null);
        $holidayLabel = $incoming['holiday_label'];
        $leaveNote = $leaves->map(fn (array $leave): string => "{$leave['label']} {$leave['from']}–{$leave['to']}")->implode('، ');

        // Excel sheets say explicitly whether a day is a work day and carry the user's own note.
        if (array_key_exists('is_work_day', $incoming)) {
            return new AttendanceDayEntity(
                id: $current?->id,
                date: $date,
                isWorkDay: $incoming['is_work_day'],
                note: $incoming['note'] ?? null,
                pairs: $hasIncomingTimes ? $incomingPairs : $base->pairs,
            );
        }

        return new AttendanceDayEntity(
            id: $current?->id,
            date: $date,
            isWorkDay: $holidayLabel === null ? $base->isWorkDay : false,
            note: match (true) {
                $holidayLabel !== null && str_contains($holidayLabel, 'جمعه') => 'جمعه',
                $holidayLabel !== null => $holidayLabel,
                $leaveNote !== '' => $leaveNote,
                default => $base->note,
            },
            pairs: $hasIncomingTimes ? $incomingPairs : $base->pairs,
        );
    }

    /**
     * Add paid hourly leaves to the day's pairs and keep the pairs in time order.
     *
     * @param  list<array{arrive: ?string, leave: ?string}>  $pairs
     * @param  Collection<int, array{label: string, from: string, to: string}>  $leaves
     * @return list<array{arrive: ?string, leave: ?string}>
     */
    private function withPaidLeaves(array $pairs, Collection $leaves): array
    {
        $paidLeaves = $leaves->reject(fn (array $leave): bool => str_contains($leave['label'], 'بدون حقوق'));

        if ($paidLeaves->isEmpty()) {
            return $pairs;
        }

        return collect($pairs)
            ->filter(fn (array $pair): bool => $pair['arrive'] !== null || $pair['leave'] !== null)
            ->merge($paidLeaves->map(fn (array $leave): array => ['arrive' => $leave['from'], 'leave' => $leave['to']]))
            ->sortBy(fn (array $pair): string => $pair['arrive'] ?? $pair['leave'])
            ->values()
            ->all();
    }

    /**
     * @param  array{date: string, pairs: list<array{arrive: ?string, leave: ?string}>, holiday_label: ?string}  $incoming
     */
    private function status(array $incoming, ?AttendanceDayEntity $current, AttendanceDayEntity $merged): string
    {
        $base = $current ?? AttendanceDayEntity::blank($merged->date);
        $isSame = $base->pairs === $merged->pairs && $base->isWorkDay === $merged->isWorkDay && $base->note === $merged->note;

        return match (true) {
            // Nothing to save: no times and nothing that differs from a blank day.
            $current === null && ! $merged->hasAnyTime() && $incoming['holiday_label'] === null && $isSame => self::STATUS_EMPTY,
            $current === null => self::STATUS_NEW,
            $isSame => $merged->hasAnyTime() || $incoming['holiday_label'] !== null ? self::STATUS_UNCHANGED : self::STATUS_EMPTY,
            default => self::STATUS_CHANGED,
        };
    }
}
