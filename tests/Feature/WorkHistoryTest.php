<?php

namespace Tests\Feature;

use App\Entities\AttendanceDayEntity;
use App\Entities\PayrollMonthEntity;
use App\Repositories\AttendanceDayRepository;
use App\Repositories\PayrollMonthRepository;
use App\Support\WorkHistory;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class WorkHistoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-09-24 12:00:00');
    }

    public function test_summary_is_empty_without_recorded_days_and_the_home_page_explains_it(): void
    {
        $this->assertNull(app(WorkHistory::class)->summarize(today()));

        $this->get(route('home'))->assertOk()->assertSee('کارنامه کاری‌ات');
    }

    public function test_summary_counts_tenure_hours_extremes_and_money_of_finished_months(): void
    {
        // Mordad 1405 (27 Tir – 26 Mordad) is finished and has saved settings: salary 2,600,000, 8 hours a day.
        app(PayrollMonthRepository::class)->save(new PayrollMonthEntity(null, 1405, 5, 2_600_000, 480, 26, 1.4, 7, 10, 12_000_000, 0));

        $this->saveDay('2026-07-18', [['07:30', '16:30']]);                 // 9:00, earliest arrival
        $this->saveDay('2026-07-19', [['09:00', '12:00'], ['13:00', '20:00']]); // 10:00, latest leave, longest day
        $this->saveDay('2026-07-24', [], isWorkDay: false);                 // Friday
        $this->saveDay('2026-09-22', [['08:00', '16:00']]);                 // Mehr 1405, not finished yet

        $history = app(WorkHistory::class)->summarize(today());

        $this->assertSame('2026-07-18', $history->firstDay->toDateString());
        $this->assertSame([0, 2, 6], [$history->tenure->y, $history->tenure->m, $history->tenure->d]);
        $this->assertSame(3, $history->attendedDays);
        $this->assertSame(27 * 60, $history->workedMinutes);
        $this->assertSame(['07:30', '2026-07-18'], [$history->earliestArrival['time'], $history->earliestArrival['date']->toDateString()]);
        $this->assertSame(['20:00', '2026-07-19'], [$history->latestLeave['time'], $history->latestLeave['date']->toDateString()]);
        $this->assertSame(10 * 60, $history->longestDay['minutes']);

        // Only the two recorded Mordad work days are required: 19 h worked − 16 h required = +3 h.
        $this->assertSame(3 * 60, $history->overtimeMinutes);
        // Hourly rate 12,500 × 1.4 × 3 h = 52,500 overtime pay.
        $this->assertSame(1, $history->paidMonths);
        $this->assertSame(2_600_000 + 52_500, $history->receivedAmount);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('کارنامه کاری من')
            ->assertSee('۲ ماه و ۶ روز')
            ->assertSee('۲٬۶۵۲٬۵۰۰')
            ->assertSee('data-sensitive', false);
    }

    /**
     * @param  list<array{0: string, 1: string}>  $pairs
     */
    private function saveDay(string $date, array $pairs, bool $isWorkDay = true): void
    {
        app(AttendanceDayRepository::class)->saveMany([new AttendanceDayEntity(
            id: null,
            date: CarbonImmutable::parse($date),
            isWorkDay: $isWorkDay,
            note: null,
            pairs: array_map(
                fn (int $index): array => ['arrive' => $pairs[$index][0] ?? null, 'leave' => $pairs[$index][1] ?? null],
                range(0, AttendanceDayEntity::PAIRS_PER_DAY - 1),
            ),
        )]);
    }
}
