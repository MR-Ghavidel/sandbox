<?php

namespace App\Http\Controllers;

use App\Entities\AttendanceDayEntity;
use App\Entities\PayrollMonthEntity;
use App\Http\Requests\UpdateAttendanceDaysRequest;
use App\Http\Requests\UpdatePayrollSettingsRequest;
use App\Repositories\AttendanceDayRepository;
use App\Repositories\PayrollMonthRepository;
use App\Support\PayrollCalculator;
use App\Support\PayrollPeriod;
use App\Support\TimeInput;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PayrollController extends Controller
{
    public function __construct(
        private PayrollMonthRepository $payrollMonths,
        private AttendanceDayRepository $attendanceDays,
    ) {}

    /**
     * Redirect to the period that today belongs to.
     */
    public function index(): RedirectResponse
    {
        $period = PayrollPeriod::containing(today());

        return to_route('payroll.show', ['year' => $period->year, 'month' => $period->month]);
    }

    /**
     * Show a month's settings, days table and salary calculation.
     */
    public function show(PayrollCalculator $calculator, int $year, int $month): View
    {
        $period = PayrollPeriod::for($year, $month);
        $settings = $this->payrollMonths->findOrDefaults($year, $month);
        $days = $this->attendanceDays->getForPeriod($period);

        return view('payroll.show', [
            'period' => $period,
            'settings' => $settings,
            'days' => $days,
            'summary' => $calculator->calculate($settings, $days, today()),
        ]);
    }

    /**
     * Save a month's salary settings.
     */
    public function updateSettings(UpdatePayrollSettingsRequest $request, int $year, int $month): RedirectResponse
    {
        $this->payrollMonths->save(new PayrollMonthEntity(
            id: null,
            year: $year,
            month: $month,
            salary: $request->integer('salary'),
            dailyWorkMinutes: AttendanceDayEntity::toMinutes($request->validated('daily_work_time')),
            salaryDivisorDays: $request->integer('salary_divisor_days'),
            overtimeMultiplier: $request->float('overtime_multiplier'),
            insuranceRatePercent: $request->float('insurance_rate_percent'),
            taxRatePercent: $request->float('tax_rate_percent'),
            taxExemption: $request->integer('tax_exemption'),
            advance: $request->integer('advance'),
        ));

        return to_route('payroll.show', ['year' => $year, 'month' => $month])->with('status', 'تنظیمات ماه ذخیره شد.');
    }

    /**
     * Save the arrive/leave times of the period's days.
     */
    public function updateDays(UpdateAttendanceDaysRequest $request, int $year, int $month): RedirectResponse
    {
        $period = PayrollPeriod::for($year, $month);

        $days = collect($request->validated('days'))
            ->filter(fn (array $day, string $date): bool => $this->isDateInPeriod($date, $period))
            ->map(fn (array $day, string $date): AttendanceDayEntity => new AttendanceDayEntity(
                id: null,
                date: CarbonImmutable::parse($date),
                isWorkDay: (bool) $day['is_work_day'],
                note: filled($day['note'] ?? null) ? trim($day['note']) : null,
                pairs: array_map(fn (int $pair): array => [
                    'arrive' => $day['arrive'][$pair] ?? null,
                    'leave' => $day['leave'][$pair] ?? null,
                ], range(1, AttendanceDayEntity::PAIRS_PER_DAY)),
            ));

        $this->attendanceDays->saveMany($days->values());

        return to_route('payroll.show', ['year' => $year, 'month' => $month])->with('status', 'ساعت‌های ورود و خروج ذخیره شد.');
    }

    private function isDateInPeriod(string $date, PayrollPeriod $period): bool
    {
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', TimeInput::latinDigits($date)) === 1
            && $period->contains(CarbonImmutable::parse($date));
    }
}
