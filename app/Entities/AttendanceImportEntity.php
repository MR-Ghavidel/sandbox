<?php

namespace App\Entities;

use Carbon\CarbonImmutable;

/**
 * A single row of the "attendance_imports" table: days received from Bizagi or Excel, waiting to be reviewed.
 */
final readonly class AttendanceImportEntity
{
    /**
     * @param  list<array{date: string, pairs: list<array{arrive: ?string, leave: ?string}>, holiday_label: ?string, is_work_day?: bool, note?: ?string}>  $days  Dates as "Y-m-d".
     * @param  list<array{year: int, month: int, salary: int, daily_work_minutes: int, salary_divisor_days: int, advance: int}>  $payrollMonths  Settings found in Excel sheets.
     */
    public function __construct(
        public int $id,
        public string $source,
        public array $days,
        public array $payrollMonths,
        public ?CarbonImmutable $appliedAt,
        public ?CarbonImmutable $createdAt,
    ) {}

    /**
     * Build an entity from a raw database row returned by the query builder.
     *
     * @param  object{id: int|string, source: string, days: string, payroll_months: ?string, applied_at: ?string, created_at: ?string}  $row
     */
    public static function fromRow(object $row): self
    {
        return new self(
            id: (int) $row->id,
            source: $row->source,
            days: json_decode($row->days, true, flags: JSON_THROW_ON_ERROR),
            payrollMonths: $row->payroll_months ? json_decode($row->payroll_months, true, flags: JSON_THROW_ON_ERROR) : [],
            appliedAt: $row->applied_at ? CarbonImmutable::parse($row->applied_at) : null,
            createdAt: $row->created_at ? CarbonImmutable::parse($row->created_at) : null,
        );
    }

    public function isApplied(): bool
    {
        return $this->appliedAt !== null;
    }
}
