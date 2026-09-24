@props(['summary', 'settings'])

@use('App\Support\Duration')
@use('Illuminate\Support\Number')

<section class="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 shadow-sm">
    <h2 class="border-b border-slate-100 dark:border-slate-800 px-4 py-3 font-bold">محاسبه حقوق</h2>

    <dl class="divide-y divide-slate-100 dark:divide-slate-800 text-sm">
        <div class="flex justify-between px-4 py-2">
            <dt class="text-slate-500 dark:text-slate-400">حقوق پایه</dt>
            <dd data-sensitive>{{ Number::format($settings->salary, locale: 'fa') }}</dd>
        </div>
        <div class="flex justify-between px-4 py-2">
            <dt class="text-slate-500 dark:text-slate-400">نرخ هر ساعت</dt>
            <dd data-sensitive>{{ Number::format($summary->hourlyRate, precision: 0, locale: 'fa') }}</dd>
        </div>
        @if ($summary->hasOvertime())
            <div class="flex justify-between px-4 py-2">
                <dt class="text-slate-500 dark:text-slate-400">اضافه‌کار <span dir="ltr">({{ Duration::format($summary->differenceMinutes) }} × {{ Number::format($settings->overtimeMultiplier, maxPrecision: 2, locale: 'fa') }})</span></dt>
                <dd data-sensitive class="text-emerald-700 dark:text-emerald-300">+{{ Number::format($summary->overtimePay, locale: 'fa') }}</dd>
            </div>
        @endif
        @if ($summary->hasDeficit())
            <div class="flex justify-between px-4 py-2">
                <dt class="text-slate-500 dark:text-slate-400">کسر کار <span dir="ltr">({{ Duration::format(-$summary->differenceMinutes) }})</span></dt>
                <dd data-sensitive class="text-red-700 dark:text-red-300">−{{ Number::format($summary->deduction, locale: 'fa') }}</dd>
            </div>
        @endif
        @if ($summary->advance > 0)
            <div class="flex justify-between px-4 py-2">
                <dt class="text-slate-500 dark:text-slate-400">مساعده</dt>
                <dd data-sensitive class="text-red-700 dark:text-red-300">−{{ Number::format($summary->advance, locale: 'fa') }}</dd>
            </div>
        @endif
        <div class="flex justify-between bg-sky-50 dark:bg-sky-950/40 px-4 py-2.5 font-bold">
            <dt>مبلغ دریافتی</dt>
            <dd data-sensitive class="text-sky-800 dark:text-sky-200">{{ Number::format($summary->finalAmount, locale: 'fa') }}</dd>
        </div>
    </dl>

    <div class="border-t border-slate-100 dark:border-slate-800 px-4 py-3 text-xs text-slate-500 dark:text-slate-400">
        <p class="mb-1 font-medium text-slate-600 dark:text-slate-300">فقط برای اطلاع (از مبلغ کم نشده):</p>
        <div class="flex justify-between">
            <span>بیمه ({{ Number::format($settings->insuranceRatePercent, maxPrecision: 2, locale: 'fa') }}٪)</span>
            <span data-sensitive>{{ Number::format($summary->insurance, locale: 'fa') }}</span>
        </div>
        <div class="flex justify-between">
            <span>مالیات ({{ Number::format($settings->taxRatePercent, maxPrecision: 2, locale: 'fa') }}٪ مازاد بر {{ Number::format($settings->taxExemption, locale: 'fa') }})</span>
            <span data-sensitive>{{ Number::format($summary->tax, locale: 'fa') }}</span>
        </div>
    </div>
</section>
