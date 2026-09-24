@props(['period', 'monthsWithData'])

@use('App\Support\JalaliDate')
@use('App\Support\PayrollPeriod')

@php($currentPeriod = PayrollPeriod::containing(today()))

{{-- Calendar button that opens a year/month picker; the grid is rendered by resources/js/month-picker.js. --}}
<div
    data-month-picker
    data-base-url="{{ url('payroll') }}"
    data-selected="{{ $period->year }}-{{ $period->month }}"
    data-current="{{ $currentPeriod->year }}-{{ $currentPeriod->month }}"
    data-months-with-data='@json($monthsWithData)'
    data-month-names='@json(array_map(fn (int $month): string => JalaliDate::monthName($month), range(1, 12)))'
    class="relative"
>
    <button type="button" data-month-picker-toggle aria-haspopup="dialog" aria-expanded="false" title="انتخاب ماه" aria-label="انتخاب ماه از تقویم" class="flex items-center rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 p-2 text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 hover:text-sky-700 dark:hover:text-sky-300 aria-expanded:border-sky-300 dark:aria-expanded:border-sky-700 aria-expanded:bg-sky-50 dark:aria-expanded:bg-sky-950/40 aria-expanded:text-sky-700 dark:aria-expanded:text-sky-300">
        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5m-9-6h.008v.008H12v-.008ZM12 15h.008v.008H12V15Zm0 2.25h.008v.008H12v-.008ZM9.75 15h.008v.008H9.75V15Zm0 2.25h.008v.008H9.75v-.008ZM7.5 15h.008v.008H7.5V15Zm0 2.25h.008v.008H7.5v-.008Zm6.75-4.5h.008v.008h-.008v-.008Zm0 2.25h.008v.008h-.008V15Zm0 2.25h.008v.008h-.008v-.008Zm2.25-4.5h.008v.008H16.5v-.008Zm0 2.25h.008v.008H16.5V15Z" /></svg>
    </button>

    <div
        data-month-picker-panel
        role="dialog"
        aria-label="انتخاب سال و ماه"
        class="pointer-events-none invisible absolute end-0 top-full z-30 mt-2 w-72 origin-top-left scale-95 rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 p-3 opacity-0 shadow-xl ring-1 ring-slate-900/5 dark:ring-white/10 transition duration-150 ease-out data-[open]:pointer-events-auto data-[open]:visible data-[open]:scale-100 data-[open]:opacity-100"
    >
        <div class="mb-3 flex items-center justify-between">
            <button type="button" data-year-step="-1" class="rounded-lg p-1.5 text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-800 dark:hover:text-slate-100" aria-label="سال قبل">
                <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
            </button>
            <span data-picker-year class="text-lg font-bold tabular-nums" aria-live="polite"></span>
            <button type="button" data-year-step="1" class="rounded-lg p-1.5 text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-800 dark:hover:text-slate-100" aria-label="سال بعد">
                <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" /></svg>
            </button>
        </div>

        <div data-picker-months class="grid grid-cols-3 gap-1.5"></div>

        <div class="mt-3 flex items-center justify-between border-t border-slate-100 dark:border-slate-800 pt-2.5 text-xs">
            <span class="flex items-center gap-1.5 text-slate-500 dark:text-slate-400"><span class="size-1.5 rounded-full bg-emerald-500"></span> دارای داده</span>
            <a href="{{ route('payroll.index') }}" class="rounded-md px-2 py-1 font-medium text-sky-700 dark:text-sky-300 hover:bg-sky-50 dark:hover:bg-sky-950/50">ماه جاری</a>
        </div>
    </div>
</div>
