<?php

namespace App\Repositories;

use App\Entities\PayrollMonthEntity;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * All database access for the "payroll_months" table, using the query builder.
 */
class PayrollMonthRepository
{
    public function find(int $year, int $month): ?PayrollMonthEntity
    {
        $row = $this->query()->where('year', $year)->where('month', $month)->first();

        return $row ? PayrollMonthEntity::fromRow($row) : null;
    }

    /**
     * Get the saved settings of a month, or unsaved defaults copied from the latest earlier month.
     */
    public function findOrDefaults(int $year, int $month): PayrollMonthEntity
    {
        return $this->find($year, $month)
            ?? PayrollMonthEntity::defaultsFor($year, $month, $this->latestBefore($year, $month));
    }

    /**
     * Get the settings of the latest month before the given one.
     */
    public function latestBefore(int $year, int $month): ?PayrollMonthEntity
    {
        $row = $this->query()
            ->where(fn (Builder $query) => $query
                ->where('year', '<', $year)
                ->orWhere(fn (Builder $query) => $query->where('year', $year)->where('month', '<', $month)))
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->first();

        return $row ? PayrollMonthEntity::fromRow($row) : null;
    }

    /**
     * Get the year and month of every month with saved settings.
     *
     * @return list<array{year: int, month: int}>
     */
    public function getSavedMonths(): array
    {
        return $this->query()
            ->orderBy('year')
            ->orderBy('month')
            ->get(['year', 'month'])
            ->map(fn (object $row): array => ['year' => (int) $row->year, 'month' => (int) $row->month])
            ->all();
    }

    /**
     * Insert or update the settings of a month.
     */
    public function save(PayrollMonthEntity $settings): void
    {
        $this->query()->upsert([
            'year' => $settings->year,
            'month' => $settings->month,
            'salary' => $settings->salary,
            'daily_work_minutes' => $settings->dailyWorkMinutes,
            'salary_divisor_days' => $settings->salaryDivisorDays,
            'overtime_multiplier' => $settings->overtimeMultiplier,
            'insurance_rate_percent' => $settings->insuranceRatePercent,
            'tax_rate_percent' => $settings->taxRatePercent,
            'tax_exemption' => $settings->taxExemption,
            'advance' => $settings->advance,
            'created_at' => now(),
            'updated_at' => now(),
        ], uniqueBy: ['year', 'month'], update: [
            'salary', 'daily_work_minutes', 'salary_divisor_days', 'overtime_multiplier',
            'insurance_rate_percent', 'tax_rate_percent', 'tax_exemption', 'advance', 'updated_at',
        ]);
    }

    private function query(): Builder
    {
        return DB::table('payroll_months');
    }
}
