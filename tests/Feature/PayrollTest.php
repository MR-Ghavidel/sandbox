<?php

namespace Tests\Feature;

use App\Repositories\AttendanceDayRepository;
use App\Repositories\PayrollMonthRepository;
use App\Support\PayrollPeriod;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PayrollTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // 1 Mehr 1405, inside the Mehr period (27 Shahrivar – 26 Mehr).
        Carbon::setTestNow('2026-09-23 10:00:00');
    }

    public function test_index_redirects_to_the_current_period(): void
    {
        $this->get(route('payroll.index'))->assertRedirect(route('payroll.show', ['year' => 1405, 'month' => 7]));
    }

    public function test_period_page_lists_every_day_and_is_in_the_drawer_menu(): void
    {
        $this->get(route('payroll.show', ['year' => 1405, 'month' => 5]))
            ->assertOk()
            ->assertSee('کارکرد مرداد ۱۴۰۵')
            ->assertSee('۲۷ تیر')
            ->assertSee('۲۶ مرداد')
            ->assertSee('name="days[2026-07-18][arrive][1]"', false)
            ->assertSee('name="days[2026-08-17][arrive][1]"', false)
            ->assertDontSee('name="days[2026-08-18][arrive][1]"', false)
            ->assertSee('href="'.route('payroll.index').'"', false);
    }

    public function test_invalid_month_is_not_found(): void
    {
        $this->get('/payroll/1405/13')->assertNotFound();
    }

    public function test_settings_are_saved_and_copied_to_the_next_month_as_defaults(): void
    {
        $this->put(route('payroll.settings.update', ['year' => 1405, 'month' => 5]), [
            'salary' => '۱۳٬۴۹۰٬۰۰۰',
            'daily_work_time' => '8:00',
            'salary_divisor_days' => '26',
            'overtime_multiplier' => '1.4',
            'insurance_rate_percent' => '7',
            'tax_rate_percent' => '10',
            'tax_exemption' => '12,000,000',
            'advance' => '500000',
        ])->assertRedirect(route('payroll.show', ['year' => 1405, 'month' => 5]));

        $repository = app(PayrollMonthRepository::class);
        $saved = $repository->find(1405, 5);
        $this->assertSame(13_490_000, $saved->salary);
        $this->assertSame(480, $saved->dailyWorkMinutes);
        $this->assertSame(500_000, $saved->advance);

        $nextMonthDefaults = $repository->findOrDefaults(1405, 6);
        $this->assertNull($nextMonthDefaults->id);
        $this->assertSame(13_490_000, $nextMonthDefaults->salary);
        $this->assertSame(0, $nextMonthDefaults->advance);
    }

    public function test_invalid_settings_are_rejected(): void
    {
        $this->put(route('payroll.settings.update', ['year' => 1405, 'month' => 5]), [
            'salary' => 'abc',
            'daily_work_time' => '25:00',
        ])->assertSessionHasErrors(['salary', 'daily_work_time', 'salary_divisor_days']);

        $this->assertDatabaseCount('payroll_months', 0);
    }

    public function test_days_are_saved_with_normalized_times_and_shown_in_the_summary(): void
    {
        $this->put(route('payroll.days.update', ['year' => 1405, 'month' => 5]), [
            'days' => [
                '2026-07-18' => ['is_work_day' => '1', 'note' => '', 'arrive' => [1 => '8:39'], 'leave' => [1 => '۱۶:۴۱']],
                '2026-07-24' => ['is_work_day' => '0', 'note' => 'جمعه', 'arrive' => [], 'leave' => []],
                '2026-09-01' => ['is_work_day' => '1', 'note' => '', 'arrive' => [1 => '08:00'], 'leave' => [1 => '16:00']], // outside the period
            ],
        ])->assertRedirect(route('payroll.show', ['year' => 1405, 'month' => 5]));

        $days = app(AttendanceDayRepository::class)->getForPeriod(PayrollPeriod::for(1405, 5));
        $this->assertSame(['arrive' => '08:39', 'leave' => '16:41'], $days->first()->pairs[0]);
        $this->assertSame(8 * 60 + 2, $days->first()->workedMinutes());
        $this->assertFalse($days->firstWhere(fn ($day) => $day->date->toDateString() === '2026-07-24')->isWorkDay);
        $this->assertDatabaseMissing('attendance_days', ['date' => '2026-09-01']);

        $this->get(route('payroll.show', ['year' => 1405, 'month' => 5]))->assertSee('۸:۰۲');
    }

    public function test_invalid_times_are_rejected(): void
    {
        $this->put(route('payroll.days.update', ['year' => 1405, 'month' => 5]), [
            'days' => ['2026-07-18' => ['is_work_day' => '1', 'arrive' => [1 => '8:61'], 'leave' => [1 => 'ab']]],
        ])->assertSessionHasErrors(['days.2026-07-18.arrive.1', 'days.2026-07-18.leave.1']);

        $this->assertSame(0, DB::table('attendance_days')->count());
    }
}
