<?php

namespace Tests\Feature;

use App\Repositories\AttendanceDayRepository;
use App\Repositories\PayrollMonthRepository;
use App\Support\PayrollCalculator;
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
            ->assertSee('data-save-url="'.$this->dayUrl(1405, 5, '2026-07-18').'"', false)
            ->assertSee('data-save-url="'.$this->dayUrl(1405, 5, '2026-08-17').'"', false)
            ->assertDontSee('data-save-url="'.$this->dayUrl(1405, 5, '2026-08-18').'"', false)
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

    public function test_a_day_is_auto_saved_with_normalized_times_and_returns_the_updated_summary(): void
    {
        $response = $this->putJson($this->dayUrl(1405, 5, '2026-07-18'), [
            'is_work_day' => true,
            'note' => '',
            'arrive' => [1 => '8:39', 2 => '830'],
            'leave' => [1 => '۱۶:۴۱'],
        ])->assertOk();

        $response->assertJson([
            'pairs' => [['arrive' => '08:39', 'leave' => '16:41'], ['arrive' => '08:30', 'leave' => null], ['arrive' => null, 'leave' => null], ['arrive' => null, 'leave' => null]],
            'total_label' => '۸:۰۲',
            'is_work_day' => true,
            'is_incomplete' => true,
            'is_off_day_work' => false,
        ]);
        $this->assertStringContainsString('کارکرد', $response->json('summary_html'));
        $this->assertStringContainsString('محاسبه حقوق', $response->json('breakdown_html'));

        $day = app(AttendanceDayRepository::class)->getForPeriod(PayrollPeriod::for(1405, 5))->first();
        $this->assertSame(8 * 60 + 2, $day->workedMinutes());

        $this->get(route('payroll.show', ['year' => 1405, 'month' => 5]))->assertSee('۸:۰۲');
    }

    public function test_work_on_an_official_holiday_is_counted_as_overtime(): void
    {
        $this->putJson($this->dayUrl(1405, 5, '2026-08-04'), [
            'is_work_day' => false,
            'note' => 'تعطیل رسمی',
            'arrive' => [1 => '09:00'],
            'leave' => [1 => '13:00'],
        ])->assertOk()->assertJson(['is_off_day_work' => true]);

        // The 4 hours count as worked time although the day adds nothing to the required time.
        $settings = app(PayrollMonthRepository::class)->findOrDefaults(1405, 5);
        $summary = app(PayrollCalculator::class)->calculate(
            $settings,
            app(AttendanceDayRepository::class)->getForPeriod(PayrollPeriod::for(1405, 5)),
            today(),
        );

        $this->assertSame(4 * 60, $summary->workedMinutes);
        $this->assertSame($summary->countedWorkDays * $settings->dailyWorkMinutes, $summary->requiredMinutes);
        $this->assertSame(4 * 60 - $summary->requiredMinutes, $summary->differenceMinutes);
    }

    public function test_auto_save_rejects_invalid_times_and_days_outside_the_period(): void
    {
        $this->putJson($this->dayUrl(1405, 5, '2026-07-18'), ['is_work_day' => true, 'arrive' => [1 => '8:61'], 'leave' => [1 => 'ab']])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['arrive.1', 'leave.1']);

        $this->putJson($this->dayUrl(1405, 5, '2026-09-01'), ['is_work_day' => true])->assertNotFound();

        $this->assertSame(0, DB::table('attendance_days')->count());
    }

    public function test_second_half_of_the_year_closes_on_the_25th(): void
    {
        $this->get(route('payroll.show', ['year' => 1405, 'month' => 8]))
            ->assertOk()
            ->assertSee('۲۶ مهر')
            ->assertSee('۲۵ آبان')
            ->assertSee('data-save-url="'.$this->dayUrl(1405, 8, '2026-10-18').'"', false) // 26 Mehr
            ->assertDontSee('data-save-url="'.$this->dayUrl(1405, 8, '2026-11-17').'"', false); // 26 Aban
    }

    public function test_amount_settings_are_shown_with_thousands_separators(): void
    {
        $this->put(route('payroll.settings.update', ['year' => 1405, 'month' => 5]), [
            'salary' => '13490000', 'daily_work_time' => '8:00', 'salary_divisor_days' => '26', 'overtime_multiplier' => '1.4',
            'insurance_rate_percent' => '7', 'tax_rate_percent' => '10', 'tax_exemption' => '12000000', 'advance' => '0',
        ]);

        $this->get(route('payroll.show', ['year' => 1405, 'month' => 5]))
            ->assertSee('value="13,490,000"', false)
            ->assertSee('value="12,000,000"', false)
            ->assertSee('data-amount-input', false);
    }

    public function test_salary_amounts_are_marked_for_privacy_mode(): void
    {
        $this->get(route('payroll.show', ['year' => 1405, 'month' => 5]))
            ->assertOk()
            ->assertSee('data-privacy-toggle', false)
            ->assertSee('localStorage.getItem(\'hide-amounts\')', false)
            ->assertSee('data-amount-input data-sensitive', false)
            ->assertSee('text-sky-800" data-sensitive', false);
    }

    private function dayUrl(int $year, int $month, string $date): string
    {
        return route('payroll.days.update', ['year' => $year, 'month' => $month, 'date' => $date]);
    }
}
