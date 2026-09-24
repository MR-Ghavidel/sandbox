<?php

namespace App\Http\Controllers;

use App\Entities\AttendanceDayEntity;
use App\Entities\PayrollMonthEntity;
use App\Http\Requests\UpdateAttendanceDayRequest;
use App\Http\Requests\UpdatePayrollSettingsRequest;
use App\Repositories\AttendanceDayRepository;
use App\Repositories\PayrollMonthRepository;
use App\Support\Duration;
use App\Support\PayrollCalculator;
use App\Support\PayrollPeriod;
use App\Support\TimeInput;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Blade;
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
     * Auto-save one day of the attendance table and return its totals and the updated summary.
     */
    public function updateDay(UpdateAttendanceDayRequest $request, PayrollCalculator $calculator, int $year, int $month, string $date): JsonResponse
    {
        $period = PayrollPeriod::for($year, $month);
        abort_unless($this->isDateInPeriod($date, $period), 404);

        $day = $request->toEntity(CarbonImmutable::parse($date));
        $this->attendanceDays->saveMany([$day]);

        $settings = $this->payrollMonths->findOrDefaults($year, $month);
        $summary = $calculator->calculate($settings, $this->attendanceDays->getForPeriod($period), today());

        return response()->json([
            'pairs' => $day->pairs,
            'total_label' => $day->workedMinutes() > 0 ? Duration::format($day->workedMinutes()) : '',
            'is_work_day' => $day->isWorkDay,
            'is_incomplete' => $day->hasIncompletePair(),
            'is_off_day_work' => ! $day->isWorkDay && $day->workedMinutes() > 0,
            'summary_html' => Blade::render('<x-payroll.summary :summary="$summary" :settings="$settings" />', compact('summary', 'settings')),
            'breakdown_html' => Blade::render('<x-payroll.breakdown :summary="$summary" :settings="$settings" />', compact('summary', 'settings')),
        ]);
    }

    private function isDateInPeriod(string $date, PayrollPeriod $period): bool
    {
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', TimeInput::latinDigits($date)) === 1
            && $period->contains(CarbonImmutable::parse($date));
    }
}
