<?php

namespace App\Repositories;

use App\Entities\AttendanceImportEntity;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * All database access for the "attendance_imports" table, using the query builder.
 */
class AttendanceImportRepository
{
    /**
     * Store received days and return the new import's id.
     *
     * @param  list<array{date: string, pairs: list<array{arrive: ?string, leave: ?string}>, holiday_label: ?string, is_work_day?: bool, note?: ?string}>  $days
     * @param  list<array{year: int, month: int, salary: int, daily_work_minutes: int, salary_divisor_days: int, advance: int}>  $payrollMonths
     */
    public function create(string $source, array $days, array $payrollMonths = []): int
    {
        return $this->query()->insertGetId([
            'source' => $source,
            'days' => json_encode($days, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
            'payroll_months' => $payrollMonths === [] ? null : json_encode($payrollMonths, JSON_THROW_ON_ERROR),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Find an import by its id or abort with a 404.
     */
    public function findOrFail(int $id): AttendanceImportEntity
    {
        $row = $this->query()->where('id', $id)->first();

        return $row ? AttendanceImportEntity::fromRow($row) : abort(404);
    }

    public function markApplied(int $id): void
    {
        $this->query()->where('id', $id)->update(['applied_at' => now(), 'updated_at' => now()]);
    }

    private function query(): Builder
    {
        return DB::table('attendance_imports');
    }
}
