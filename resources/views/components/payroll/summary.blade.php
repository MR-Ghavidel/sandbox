@props(['summary', 'settings'])

@use('App\Support\Duration')
@use('Illuminate\Support\Number')

<div class="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
    <div class="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 p-4 shadow-sm">
        <p class="text-sm text-slate-500 dark:text-slate-400">کارکرد</p>
        <p class="mt-1 text-2xl font-bold" dir="ltr">{{ Duration::format($summary->workedMinutes) }}</p>
        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">موظفی تا امروز: <span dir="ltr">{{ Duration::format($summary->requiredMinutes) }}</span></p>
    </div>

    <div @class([
        'rounded-xl border p-4 shadow-sm',
        'border-emerald-200 dark:border-emerald-800 bg-emerald-50 dark:bg-emerald-950/40' => $summary->hasOvertime(),
        'border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-950/40' => $summary->hasDeficit(),
        'border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900' => ! $summary->hasOvertime() && ! $summary->hasDeficit(),
    ])>
        <p class="text-sm text-slate-500 dark:text-slate-400">{{ $summary->hasDeficit() ? 'کسر کار' : 'اضافه‌کار' }}</p>
        <p @class([
            'mt-1 text-2xl font-bold',
            'text-emerald-700 dark:text-emerald-300' => $summary->hasOvertime(),
            'text-red-700 dark:text-red-300' => $summary->hasDeficit(),
        ]) dir="ltr">{{ Duration::format($summary->differenceMinutes, withPlusSign: true) }}</p>
        @if ($summary->compensationPerRemainingDayMinutes !== null)
            <p class="mt-1 text-xs text-red-700 dark:text-red-300">برای جبران: روزی <span dir="ltr">{{ Duration::format($summary->compensationPerRemainingDayMinutes) }}</span> بیشتر در {{ Number::format($summary->remainingWorkDays, locale: 'fa') }} روز باقی‌مانده</p>
        @elseif ($summary->averageDifferencePerDayMinutes !== null)
            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">میانگین روزانه: <span dir="ltr">{{ Duration::format($summary->averageDifferencePerDayMinutes, withPlusSign: true) }}</span></p>
        @endif
    </div>

    <div class="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 p-4 shadow-sm">
        <p class="text-sm text-slate-500 dark:text-slate-400">روزهای کاری</p>
        <p class="mt-1 text-2xl font-bold">{{ Number::format($summary->countedWorkDays, locale: 'fa') }} <span class="text-base font-normal text-slate-500 dark:text-slate-400">از {{ Number::format($summary->totalWorkDays, locale: 'fa') }}</span></p>
        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">ساعت کار روزانه: <span dir="ltr">{{ Duration::format($settings->dailyWorkMinutes) }}</span></p>
    </div>

    <div class="rounded-xl border border-sky-200 dark:border-sky-800 bg-gradient-to-l from-sky-50 dark:from-sky-950/40 to-white dark:to-slate-900 p-4 shadow-sm">
        <p class="text-sm text-slate-500 dark:text-slate-400">مبلغ دریافتی</p>
        <p class="mt-1 text-2xl font-bold text-sky-800 dark:text-sky-200" data-sensitive>{{ Number::format($summary->finalAmount, locale: 'fa') }}</p>
        @if ($settings->salary === 0)
            <p class="mt-1 text-xs text-amber-700 dark:text-amber-300">حقوق این ماه هنوز وارد نشده</p>
        @endif
    </div>
</div>
