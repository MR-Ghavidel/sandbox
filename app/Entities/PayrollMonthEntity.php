<?php

namespace App\Entities;

/**
 * A single row of the "payroll_months" table: the salary settings of one Jalali month.
 */
final readonly class PayrollMonthEntity
{
    public function __construct(
        public ?int $id,
        public int $year,
        public int $month,
        public int $salary,
        public int $dailyWorkMinutes,
        public int $salaryDivisorDays,
        public float $overtimeMultiplier,
        public float $insuranceRatePercent,
        public float $taxRatePercent,
        public int $taxExemption,
        public int $advance,
    ) {}

    /**
     * Build an entity from a raw database row returned by the query builder.
     *
     * @param  object{id: int|string, year: int|string, month: int|string, salary: int|string, daily_work_minutes: int|string, salary_divisor_days: int|string, overtime_multiplier: float|string, insurance_rate_percent: float|string, tax_rate_percent: float|string, tax_exemption: int|string, advance: int|string}  $row
     */
    public static function fromRow(object $row): self
    {
        return new self(
            id: (int) $row->id,
            year: (int) $row->year,
            month: (int) $row->month,
            salary: (int) $row->salary,
            dailyWorkMinutes: (int) $row->daily_work_minutes,
            salaryDivisorDays: (int) $row->salary_divisor_days,
            overtimeMultiplier: (float) $row->overtime_multiplier,
            insuranceRatePercent: (float) $row->insurance_rate_percent,
            taxRatePercent: (float) $row->tax_rate_percent,
            taxExemption: (int) $row->tax_exemption,
            advance: (int) $row->advance,
        );
    }

    /**
     * Unsaved settings for a month, copied from another month or from the defaults.
     */
    public static function defaultsFor(int $year, int $month, ?self $copyFrom = null): self
    {
        return new self(
            id: null,
            year: $year,
            month: $month,
            salary: $copyFrom->salary ?? 0,
            dailyWorkMinutes: $copyFrom->dailyWorkMinutes ?? 8 * 60,
            salaryDivisorDays: $copyFrom->salaryDivisorDays ?? 26,
            overtimeMultiplier: $copyFrom->overtimeMultiplier ?? 1.4,
            insuranceRatePercent: $copyFrom->insuranceRatePercent ?? 7,
            taxRatePercent: $copyFrom->taxRatePercent ?? 10,
            taxExemption: $copyFrom->taxExemption ?? 12_000_000,
            advance: 0,
        );
    }
}
