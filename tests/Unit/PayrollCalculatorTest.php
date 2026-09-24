<?php

namespace Tests\Unit;

use App\Entities\AttendanceDayEntity;
use App\Entities\PayrollMonthEntity;
use App\Support\JalaliDate;
use App\Support\PayrollCalculator;
use App\Support\PayrollPeriod;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Tests\TestCase;

class PayrollCalculatorTest extends TestCase
{
    public function test_mordad_1405_matches_the_excel_sheet_with_exact_minutes(): void
    {
        $summary = (new PayrollCalculator)->calculate($this->mordadSettings(), $this->mordadDays(), CarbonImmutable::parse('2026-09-01'));

        // Same as the Excel sheet: 23 work days, 184:00 required, 184:13 worked.
        $this->assertSame(23, $summary->countedWorkDays);
        $this->assertSame(184 * 60, $summary->requiredMinutes);
        $this->assertSame(184 * 60 + 13, $summary->workedMinutes);
        $this->assertSame(13, $summary->differenceMinutes);
        $this->assertEqualsWithDelta(64855.77, $summary->hourlyRate, 0.01);

        // Excel floors the 13 minutes to 0 hours; the app pays them at 1.4 × hourly rate.
        $this->assertSame(19673, $summary->overtimePay);
        $this->assertSame(0, $summary->deduction);
        $this->assertSame(13_490_000 + 19673, $summary->finalAmount);

        // Informational only: insurance 7% of (salary + overtime), tax 10% above the exemption.
        $this->assertSame((int) round((13_490_000 + 19673) * 0.07), $summary->insurance);
        $this->assertSame((int) round((13_490_000 + 19673 - 12_000_000) * 0.10), $summary->tax);
    }

    public function test_deficit_is_deducted_by_the_minute_and_advance_is_subtracted(): void
    {
        $settings = new PayrollMonthEntity(null, 1405, 5, 2_600_000, 480, 26, 1.4, 7, 10, 12_000_000, 100_000);
        $days = collect([
            $this->day('2026-07-19', true, [['08:00', '15:30']]), // 30 minutes short
            $this->day('2026-07-20', true, [['08:00', '16:00']]),
        ]);

        $summary = (new PayrollCalculator)->calculate($settings, $days, CarbonImmutable::parse('2026-07-20'));

        // Hourly rate = 2,600,000 / 26 / 8 = 12,500; 30 minutes = 6,250.
        $this->assertSame(-30, $summary->differenceMinutes);
        $this->assertSame(6250, $summary->deduction);
        $this->assertSame(2_600_000 - 6250 - 100_000, $summary->finalAmount);
    }

    public function test_work_on_a_day_off_counts_as_overtime_and_future_work_days_are_not_required_yet(): void
    {
        $settings = new PayrollMonthEntity(null, 1405, 5, 2_600_000, 480, 26, 1.4, 7, 10, 12_000_000, 0);
        $days = collect([
            $this->day('2026-07-24', false, [['10:00', '12:00']]), // Friday
            $this->day('2026-07-25', true, [['08:00', '16:00']]),
            $this->day('2026-07-26', true, []),                   // tomorrow
        ]);

        $summary = (new PayrollCalculator)->calculate($settings, $days, CarbonImmutable::parse('2026-07-25'));

        $this->assertSame(1, $summary->countedWorkDays);
        $this->assertSame(1, $summary->remainingWorkDays);
        $this->assertSame(120, $summary->differenceMinutes);
    }

    public function test_worked_minutes_skip_incomplete_pairs_and_handle_midnight(): void
    {
        $day = $this->day('2026-07-19', true, [['08:00', '12:00'], ['13:00', null], ['22:00', '01:30']]);

        $this->assertSame(4 * 60 + 3 * 60 + 30, $day->workedMinutes());
        $this->assertTrue($day->hasIncompletePair());
    }

    private function mordadSettings(): PayrollMonthEntity
    {
        return new PayrollMonthEntity(null, 1405, 5, 13_490_000, 480, 26, 1.4, 7, 10, 12_000_000, 0);
    }

    /**
     * The days of the "11_Mordad.xlsx" sheet (27 Tir – 26 Mordad 1405).
     *
     * @return Collection<int, AttendanceDayEntity>
     */
    private function mordadDays(): Collection
    {
        $sheet = [
            // [Jalali month, day, is work day, pairs]
            [4, 27, true, [['08:39', '16:41']]], [4, 28, true, [['09:02', '16:38']]], [4, 29, true, [['08:46', '16:10']]],
            [4, 30, true, [['08:41', '17:16']]], [4, 31, true, [['08:43', '18:21']]], [5, 1, true, [['08:37', '17:47']]],
            [5, 2, false, []], [5, 3, true, [['09:51', '20:13']]], [5, 4, true, [['08:39', '17:25']]],
            [5, 5, true, [['08:28', '17:13']]], [5, 6, true, [['08:37', '17:32']]], [5, 7, true, [['08:30', '16:36']]],
            [5, 8, true, [['08:51', '16:37']]], [5, 9, false, []], [5, 10, true, [['08:35', '16:47']]],
            [5, 11, true, [['08:35', '15:38']]], [5, 12, true, [['08:40', '17:01']]], [5, 13, false, []],
            [5, 14, true, []], [5, 15, false, []], [5, 16, false, []],
            [5, 17, true, [['08:49', '18:02']]], [5, 18, true, [['08:42', '17:16']]], [5, 19, true, [['08:55', '16:39']]],
            [5, 20, true, [['08:41', '16:38']]], [5, 21, false, []], [5, 22, false, []],
            [5, 23, false, []], [5, 24, true, [['08:35', '16:50']]], [5, 25, true, [['08:46', '18:30']]],
            [5, 26, true, [['08:43', '11:26'], ['14:53', '18:15']]],
        ];

        $this->assertCount(PayrollPeriod::for(1405, 5)->days()->count(), $sheet);

        return collect($sheet)->map(fn (array $row): AttendanceDayEntity => $this->day(
            JalaliDate::toGregorian(1405, $row[0], $row[1])->toDateString(),
            $row[2],
            $row[3],
        ));
    }

    /**
     * @param  list<array{0: ?string, 1: ?string}>  $pairs
     */
    private function day(string $date, bool $isWorkDay, array $pairs): AttendanceDayEntity
    {
        return new AttendanceDayEntity(
            id: null,
            date: CarbonImmutable::parse($date),
            isWorkDay: $isWorkDay,
            note: null,
            pairs: array_map(
                fn (int $index): array => ['arrive' => $pairs[$index][0] ?? null, 'leave' => $pairs[$index][1] ?? null],
                range(0, AttendanceDayEntity::PAIRS_PER_DAY - 1),
            ),
        );
    }
}
