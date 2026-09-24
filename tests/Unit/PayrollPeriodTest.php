<?php

namespace Tests\Unit;

use App\Support\JalaliDate;
use App\Support\PayrollPeriod;
use App\Support\TimeInput;
use Carbon\CarbonImmutable;
use Tests\TestCase;

class PayrollPeriodTest extends TestCase
{
    public function test_period_runs_from_the_27th_of_the_previous_month_to_the_26th(): void
    {
        $mordad = PayrollPeriod::for(1405, 5);

        $this->assertSame('2026-07-18', $mordad->startDate->toDateString()); // 27 Tir 1405
        $this->assertSame('2026-08-17', $mordad->endDate->toDateString());   // 26 Mordad 1405
        $this->assertCount(31, $mordad->days());
        $this->assertSame('مرداد ۱۴۰۵', $mordad->label());
    }

    public function test_period_containing_a_day_moves_to_the_next_month_after_the_26th(): void
    {
        $this->assertSame([1405, 6], $this->yearAndMonth(PayrollPeriod::containing(CarbonImmutable::parse('2026-09-17')))); // 26 Shahrivar
        $this->assertSame([1405, 7], $this->yearAndMonth(PayrollPeriod::containing(CarbonImmutable::parse('2026-09-18')))); // 27 Shahrivar
    }

    public function test_second_half_of_the_year_closes_on_the_25th(): void
    {
        $mehr = PayrollPeriod::for(1405, 7);
        $aban = PayrollPeriod::for(1405, 8);

        // Mehr starts the day after Shahrivar's closing day (26th), so it is one day shorter.
        $this->assertSame('2026-09-18', $mehr->startDate->toDateString()); // 27 Shahrivar
        $this->assertSame('2026-10-17', $mehr->endDate->toDateString());   // 25 Mehr
        $this->assertSame('2026-10-18', $aban->startDate->toDateString()); // 26 Mehr
        $this->assertSame('2026-11-16', $aban->endDate->toDateString());   // 25 Aban

        $this->assertSame([1405, 7], $this->yearAndMonth(PayrollPeriod::containing(CarbonImmutable::parse('2026-10-17'))));
        $this->assertSame([1405, 8], $this->yearAndMonth(PayrollPeriod::containing(CarbonImmutable::parse('2026-10-18'))));
    }

    public function test_farvardin_starts_the_day_after_esfand_closes_on_the_25th(): void
    {
        $esfand = PayrollPeriod::for(1405, 12);
        $farvardin = PayrollPeriod::for(1406, 1);

        $this->assertTrue($farvardin->startDate->equalTo($esfand->endDate->addDay()));
        $this->assertSame('۲۶ اسفند', JalaliDate::format($farvardin->startDate, 'd MMMM'));
        $this->assertSame('۲۶ فروردین', JalaliDate::format($farvardin->endDate, 'd MMMM'));
    }

    public function test_period_wraps_around_the_year(): void
    {
        $farvardin = PayrollPeriod::for(1406, 1);

        $this->assertSame([1405, 12], $this->yearAndMonth($farvardin->previous()));
        $this->assertSame([1406, 1], $this->yearAndMonth(PayrollPeriod::for(1405, 12)->next()));
        $this->assertSame([1406, 1], $this->yearAndMonth(PayrollPeriod::containing($farvardin->startDate)));
    }

    public function test_typed_times_are_normalized(): void
    {
        $this->assertSame('08:05', TimeInput::normalize('8:5'));
        $this->assertSame('08:30', TimeInput::normalize('۸:۳۰'));
        $this->assertSame('08:30', TimeInput::normalize('830'));
        $this->assertSame('17:45', TimeInput::normalize('17.45'));
        $this->assertNull(TimeInput::normalize('00:00'));
        $this->assertNull(TimeInput::normalize('  '));
        $this->assertSame('abc', TimeInput::normalize('abc'));
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function yearAndMonth(PayrollPeriod $period): array
    {
        return [$period->year, $period->month];
    }
}
