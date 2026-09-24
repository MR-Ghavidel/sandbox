@use('App\Support\JalaliDate')

<x-layouts.app :title="'کارکرد و حقوق — '.$period->label()">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold">کارکرد {{ $period->label() }}</h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                از {{ JalaliDate::format($period->startDate, 'EEEE d MMMM') }} تا {{ JalaliDate::format($period->endDate, 'EEEE d MMMM y') }}
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
        <form method="POST" action="{{ route('attendance-imports.excel') }}" enctype="multipart/form-data">
            @csrf
            <label class="inline-flex cursor-pointer items-center gap-1.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-3 py-2 text-sm hover:bg-slate-50 dark:hover:bg-slate-800" title="فایل‌های اکسل ماهانه قبلی (xlsx) را انتخاب کنید؛ قبل از ثبت، پیش‌نمایش نشان داده می‌شود">
                <svg class="size-4 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" /></svg>
                <span data-upload-label>ورود از اکسل</span>
                <input type="file" name="files[]" accept=".xlsx" multiple data-auto-submit class="sr-only">
            </label>
        </form>

        <x-payroll.month-picker :period="$period" :months-with-data="$monthsWithData" />

        <nav class="flex items-center overflow-hidden rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-sm">
            <a href="{{ route('payroll.show', ['year' => $period->previous()->year, 'month' => $period->previous()->month]) }}" class="px-3 py-2 hover:bg-slate-50 dark:hover:bg-slate-800">&rarr; {{ $period->previous()->label() }}</a>
            <a href="{{ route('payroll.index') }}" class="border-x border-slate-200 dark:border-slate-700 px-3 py-2 font-medium hover:bg-slate-50 dark:hover:bg-slate-800">ماه جاری</a>
            <a href="{{ route('payroll.show', ['year' => $period->next()->year, 'month' => $period->next()->month]) }}" class="px-3 py-2 hover:bg-slate-50 dark:hover:bg-slate-800">{{ $period->next()->label() }} &larr;</a>
        </nav>
        </div>
    </div>

    @if (session('status'))
        <div class="mb-4 rounded-lg border border-emerald-200 dark:border-emerald-800 bg-emerald-50 dark:bg-emerald-950/40 px-4 py-3 text-sm text-emerald-800 dark:text-emerald-200">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="mb-4 rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-950/40 px-4 py-3 text-sm text-red-700 dark:text-red-300">
            <ul class="list-inside list-disc">
                @foreach (collect($errors->all())->unique() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Replaced with fresh HTML after every auto-save of a day. --}}
    <div data-payroll-summary>
        <x-payroll.summary :summary="$summary" :settings="$settings" />
    </div>

    <div class="grid grid-cols-1 items-start gap-4 xl:grid-cols-[1fr_22rem]">
        <x-payroll.days-table :days="$days" :period="$period" class="min-w-0 xl:order-1" />

        <div class="space-y-4 xl:order-2">
            <div data-payroll-breakdown>
                <x-payroll.breakdown :summary="$summary" :settings="$settings" />
            </div>
            <x-payroll.settings-form :settings="$settings" :period="$period" />
        </div>
    </div>
</x-layouts.app>
