<?php

namespace App\Repositories;

use App\Entities\AttendanceDayEntity;
use App\Support\PayrollPeriod;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * All database access for the "attendance_days" table, using the query builder.
 */
class AttendanceDayRepository
{
    /**
     * Get the saved days between two dates (inclusive), keyed by "Y-m-d".
     *
     * @return Collection<string, AttendanceDayEntity>
     */
    public function getBetween(CarbonInterface $from, CarbonInterface $to): Collection
    {
        return $this->query()
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->orderBy('date')
            ->get()
            ->map(AttendanceDayEntity::fromRow(...))
            ->keyBy(fn (AttendanceDayEntity $day): string => $day->date->toDateString());
    }

    /**
     * Get every saved day, oldest first, keyed by "Y-m-d".
     *
     * @return Collection<string, AttendanceDayEntity>
     */
    public function getAll(): Collection
    {
        return $this->query()
            ->orderBy('date')
            ->get()
            ->map(AttendanceDayEntity::fromRow(...))
            ->keyBy(fn (AttendanceDayEntity $day): string => $day->date->toDateString());
    }

    /**
     * Get the distinct dates that have a saved row, oldest first.
     *
     * @return Collection<int, CarbonImmutable>
     */
    public function getSavedDates(): Collection
    {
        return $this->query()
            ->orderBy('date')
            ->pluck('date')
            ->map(fn (string $date): CarbonImmutable => CarbonImmutable::parse($date)->startOfDay());
    }

    /**
     * Get every day of a period, using a blank day where nothing is saved yet.
     *
     * @return Collection<int, AttendanceDayEntity>
     */
    public function getForPeriod(PayrollPeriod $period): Collection
    {
        $savedDays = $this->getBetween($period->startDate, $period->endDate);

        return $period->days()->map(fn (CarbonImmutable $date): AttendanceDayEntity => $savedDays->get($date->toDateString())
            ?? AttendanceDayEntity::blank($date));
    }

    /**
     * Insert or update days, matched by their date.
     *
     * @param  iterable<AttendanceDayEntity>  $days
     */
    public function saveMany(iterable $days): void
    {
        $rows = collect($days)->map(fn (AttendanceDayEntity $day): array => [
            'date' => $day->date->toDateString(),
            'is_work_day' => $day->isWorkDay,
            'note' => $day->note,
            ...collect($day->pairs)->flatMap(fn (array $pair, int $index): array => [
                'arrive_'.($index + 1) => $pair['arrive'],
                'leave_'.($index + 1) => $pair['leave'],
            ])->all(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($rows->isEmpty()) {
            return;
        }

        $this->query()->upsert(
            $rows->all(),
            uniqueBy: ['date'],
            update: collect(array_keys($rows->first()))->reject(fn (string $column): bool => in_array($column, ['date', 'created_at']))->values()->all(),
        );
    }

    private function query(): Builder
    {
        return DB::table('attendance_days');
    }
}
