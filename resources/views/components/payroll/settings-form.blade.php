@props(['settings', 'period'])

@use('App\Support\Duration')
@use('App\Support\TimeInput')

@php
    $fields = [
        ['name' => 'salary', 'label' => 'حقوق ماه', 'value' => number_format($settings->salary), 'isAmount' => true],
        ['name' => 'daily_work_time', 'label' => 'ساعت کار روزانه', 'value' => TimeInput::latinDigits(Duration::format($settings->dailyWorkMinutes)), 'placeholder' => '8:00'],
        ['name' => 'salary_divisor_days', 'label' => 'روزهای تقسیم حقوق', 'value' => $settings->salaryDivisorDays, 'hint' => 'نرخ ساعتی = حقوق ÷ این عدد ÷ ساعت کار روزانه'],
        ['name' => 'overtime_multiplier', 'label' => 'ضریب اضافه‌کار', 'value' => $settings->overtimeMultiplier],
        ['name' => 'advance', 'label' => 'مساعده', 'value' => number_format($settings->advance), 'isAmount' => true],
        ['name' => 'insurance_rate_percent', 'label' => 'درصد بیمه', 'value' => $settings->insuranceRatePercent],
        ['name' => 'tax_rate_percent', 'label' => 'درصد مالیات', 'value' => $settings->taxRatePercent],
        ['name' => 'tax_exemption', 'label' => 'معافیت مالیاتی', 'value' => number_format($settings->taxExemption), 'isAmount' => true],
    ];
@endphp

@php($hasErrors = $errors->hasAny(array_column($fields, 'name')))

<section data-collapsible="payroll-settings-panel" data-collapsed-by-default @if ($hasErrors) data-collapse-force-open @else data-collapsed @endif class="group/collapsible rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 shadow-sm">
    <div class="flex items-center justify-between px-4 py-3">
        <h2 class="font-bold">تنظیمات {{ $period->label() }}</h2>
        <x-collapse-toggle />
    </div>

    <div data-collapse-body>
    <div class="min-h-0 overflow-hidden">
    <form method="POST" action="{{ route('payroll.settings.update', ['year' => $period->year, 'month' => $period->month]) }}" class="space-y-3 border-t border-slate-100 dark:border-slate-800 p-4">
        @csrf
        @method('PUT')

        @if ($settings->id === null)
            <p class="rounded-md bg-amber-50 dark:bg-amber-950/40 px-3 py-2 text-xs text-amber-800 dark:text-amber-200">تنظیمات این ماه هنوز ذخیره نشده؛ مقادیر از ماه قبل (یا پیش‌فرض) آمده‌اند.</p>
        @endif

        @foreach ($fields as $field)
            <label class="block text-sm">
                <span class="mb-1 block font-medium">{{ $field['label'] }}</span>
                <input type="text" inputmode="{{ isset($field['isAmount']) ? 'numeric' : 'decimal' }}" dir="ltr" @if (isset($field['isAmount'])) data-amount-input data-sensitive @endif name="{{ $field['name'] }}" value="{{ old($field['name'], $field['value']) }}" placeholder="{{ $field['placeholder'] ?? '' }}" @class([
                    'w-full rounded-md border px-2 py-1.5 text-left focus:border-sky-400 dark:focus:border-sky-600 focus:outline-none',
                    'border-red-400 dark:border-red-500' => $errors->has($field['name']),
                    'border-slate-200 dark:border-slate-700' => ! $errors->has($field['name']),
                ])>
                @isset($field['hint'])
                    <span class="mt-1 block text-xs text-slate-400 dark:text-slate-500">{{ $field['hint'] }}</span>
                @endisset
            </label>
        @endforeach

        <button type="submit" class="w-full rounded-md bg-sky-600 px-3 py-2 text-sm font-medium text-white hover:bg-sky-700">ذخیره تنظیمات</button>
    </form>
    </div>
    </div>
</section>
