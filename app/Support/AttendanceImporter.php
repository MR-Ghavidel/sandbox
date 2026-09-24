<?php

namespace App\Support;

use App\Entities\AttendanceDayEntity;
use App\Entities\AttendanceImportEntity;
use App\Repositories\AttendanceDayRepository;
use App\Repositories\AttendanceImportRepository;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Compares days received from Bizagi with the saved days and applies them.
 *
 * Merge rules for each received date:
 * - Bizagi times replace the saved times. A date without any time in Bizagi keeps its saved times.
 * - A holiday in Bizagi (e.g. "تعطیلی جمعه") makes the day a non-work day.
 * - Otherwise the saved "work day" flag and note are kept, so manual choices (leave, official holidays) survive.
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

            $this->imports->markApplied($import->id);
        });
    }

    /**
     * @param  array{date: string, pairs: list<array{arrive: ?string, leave: ?string}>, holiday_label: ?string}  $incoming
     */
    private function merge(CarbonImmutable $date, array $incoming, ?AttendanceDayEntity $current): AttendanceDayEntity
    {
        $base = $current ?? AttendanceDayEntity::blank($date);
        $incomingPairs = array_pad(array_slice($incoming['pairs'], 0, AttendanceDayEntity::PAIRS_PER_DAY), AttendanceDayEntity::PAIRS_PER_DAY, ['arrive' => null, 'leave' => null]);
        $hasIncomingTimes = collect($incomingPairs)->contains(fn (array $pair): bool => $pair['arrive'] !== null || $pair['leave'] !== null);
        $holidayLabel = $incoming['holiday_label'];

        return new AttendanceDayEntity(
            id: $current?->id,
            date: $date,
            isWorkDay: $holidayLabel === null ? $base->isWorkDay : false,
            note: match (true) {
                $holidayLabel === null => $base->note,
                str_contains($holidayLabel, 'جمعه') => 'جمعه',
                default => $holidayLabel,
            },
            pairs: $hasIncomingTimes ? $incomingPairs : $base->pairs,
        );
    }

    /**
     * @param  array{date: string, pairs: list<array{arrive: ?string, leave: ?string}>, holiday_label: ?string}  $incoming
     */
    private function status(array $incoming, ?AttendanceDayEntity $current, AttendanceDayEntity $merged): string
    {
        $base = $current ?? AttendanceDayEntity::blank($merged->date);
        $isSame = $base->pairs === $merged->pairs && $base->isWorkDay === $merged->isWorkDay && $base->note === $merged->note;

        return match (true) {
            $current === null && ! $merged->hasAnyTime() && $incoming['holiday_label'] === null => self::STATUS_EMPTY,
            $current === null => self::STATUS_NEW,
            $isSame => $merged->hasAnyTime() || $incoming['holiday_label'] !== null ? self::STATUS_UNCHANGED : self::STATUS_EMPTY,
            default => self::STATUS_CHANGED,
        };
    }
}
