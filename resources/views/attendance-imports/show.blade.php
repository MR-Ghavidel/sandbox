@use('App\Support\AttendanceImporter')
@use('App\Support\Duration')
@use('App\Support\JalaliDate')
@use('Illuminate\Support\Number')

@php
    $statusLabels = [
        AttendanceImporter::STATUS_NEW => ['جدید', 'bg-sky-100 dark:bg-sky-900/50 text-sky-700 dark:text-sky-300'],
        AttendanceImporter::STATUS_CHANGED => ['تغییر', 'bg-amber-100 dark:bg-amber-900/40 text-amber-800 dark:text-amber-200'],
        AttendanceImporter::STATUS_UNCHANGED => ['بدون تغییر', 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300'],
        AttendanceImporter::STATUS_EMPTY => ['بدون داده', 'bg-slate-100 dark:bg-slate-800 text-slate-400 dark:text-slate-500'],
    ];
    $pairsText = fn (array $pairs): string => collect($pairs)
        ->filter(fn (array $pair): bool => $pair['arrive'] !== null || $pair['leave'] !== null)
        ->map(fn (array $pair): string => ($pair['arrive'] ?? '??').'–'.($pair['leave'] ?? '??'))
        ->implode('  ');
    $changesCount = $rows->whereIn('status', [AttendanceImporter::STATUS_NEW, AttendanceImporter::STATUS_CHANGED])->count();
    $newMonthsCount = $payrollMonths->where('is_new', true)->count();
    $sourceLabel = $import->source === 'excel' ? 'اکسل' : 'بیزاجی';
@endphp

<x-layouts.app :title="'بررسی داده‌های '.$sourceLabel">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold">بررسی داده‌های {{ $sourceLabel }}</h1>
            @if ($rows->isNotEmpty())
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                    {{ Number::format($rows->count(), locale: 'fa') }} روز، از {{ JalaliDate::format($rows->first()['date'], 'd MMMM') }} تا {{ JalaliDate::format($rows->last()['date'], 'd MMMM y') }}
                    — {{ $rows->pluck('period')->unique(fn ($period) => $period->label())->map->label()->implode('، ') }}
                </p>
            @endif
        </div>

        @if ($import->isApplied())
            <span class="rounded-lg bg-emerald-100 dark:bg-emerald-900/40 px-3 py-2 text-sm font-medium text-emerald-800 dark:text-emerald-200">اعمال شده در {{ JalaliDate::format($import->appliedAt, 'd MMMM، HH:mm') }}</span>
        @elseif ($changesCount > 0 || $newMonthsCount > 0)
            <form method="POST" action="{{ route('attendance-imports.apply', $import->id) }}">
                @csrf
                <button type="submit" class="rounded-lg bg-sky-600 px-4 py-2 text-sm font-medium text-white hover:bg-sky-700">ثبت {{ Number::format($changesCount, locale: 'fa') }} روز{{ $newMonthsCount > 0 ? ' و تنظیمات '.Number::format($newMonthsCount, locale: 'fa').' ماه' : '' }}</button>
            </form>
        @else
            <span class="rounded-lg bg-slate-100 dark:bg-slate-800 px-3 py-2 text-sm text-slate-600 dark:text-slate-300">همه روزها از قبل به‌روز هستند</span>
        @endif
    </div>

    @if (session('status'))
        <div class="mb-4 rounded-lg border border-emerald-200 dark:border-emerald-800 bg-emerald-50 dark:bg-emerald-950/40 px-4 py-3 text-sm text-emerald-800 dark:text-emerald-200">{{ session('status') }}</div>
    @endif

    @if ($payrollMonths->isNotEmpty())
        <section class="mb-4 overflow-x-auto rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 shadow-sm">
            <h2 class="border-b border-slate-100 dark:border-slate-800 px-4 py-3 font-bold">تنظیمات ماه‌ها</h2>
            <table class="w-full text-sm">
                <thead class="bg-slate-50 dark:bg-slate-800/60 text-xs text-slate-500 dark:text-slate-400">
                    <tr>
                        <th class="px-4 py-2 text-start font-medium">ماه</th>
                        <th class="px-4 py-2 text-start font-medium">وضعیت</th>
                        <th class="px-4 py-2 text-start font-medium">حقوق</th>
                        <th class="px-4 py-2 text-start font-medium">ساعت کار روزانه</th>
                        <th class="px-4 py-2 text-start font-medium">روزهای تقسیم</th>
                        <th class="px-4 py-2 text-start font-medium">مساعده</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @foreach ($payrollMonths as $month)
                        <tr @class(['text-slate-400 dark:text-slate-500' => ! $month['is_new']])>
                            <td class="px-4 py-2 font-medium">{{ $month['period']->label() }}</td>
                            <td class="px-4 py-2">
                                @if ($month['is_new'])
                                    <span class="rounded-full bg-sky-100 dark:bg-sky-900/50 px-2 py-0.5 text-xs text-sky-700 dark:text-sky-300">جدید</span>
                                @else
                                    <span class="rounded-full bg-slate-100 dark:bg-slate-800 px-2 py-0.5 text-xs text-slate-500 dark:text-slate-400">از قبل ذخیره شده، تغییر نمی‌کند</span>
                                @endif
                            </td>
                            <td class="px-4 py-2"><span data-sensitive>{{ Number::format($month['settings']->salary, locale: 'fa') }}</span></td>
                            <td class="px-4 py-2" dir="ltr">{{ Duration::format($month['settings']->dailyWorkMinutes) }}</td>
                            <td class="px-4 py-2">{{ Number::format($month['settings']->salaryDivisorDays, locale: 'fa') }}</td>
                            <td class="px-4 py-2"><span data-sensitive>{{ Number::format($month['settings']->advance, locale: 'fa') }}</span></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>
    @endif

    <section class="overflow-x-auto rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 shadow-sm">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 dark:bg-slate-800/60 text-xs text-slate-500 dark:text-slate-400">
                <tr>
                    <th class="px-4 py-2 text-start font-medium">روز</th>
                    <th class="px-4 py-2 text-start font-medium">وضعیت</th>
                    <th class="px-4 py-2 text-start font-medium">ذخیره‌شده فعلی</th>
                    <th class="px-4 py-2 text-start font-medium">بعد از ثبت</th>
                    <th class="px-4 py-2 font-medium">جمع</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                @foreach ($rows as $row)
                    @php([$statusLabel, $statusClasses] = $statusLabels[$row['status']])
                    <tr @class(['text-slate-400 dark:text-slate-500' => $row['status'] === AttendanceImporter::STATUS_EMPTY])>
                        <td class="px-4 py-2 whitespace-nowrap">
                            <span class="font-medium">{{ JalaliDate::format($row['date'], 'EEEE') }}</span>
                            <span class="text-xs text-slate-500 dark:text-slate-400">{{ JalaliDate::format($row['date'], 'd MMMM') }}</span>
                        </td>
                        <td class="px-4 py-2"><span class="rounded-full px-2 py-0.5 text-xs {{ $statusClasses }}">{{ $statusLabel }}</span></td>
                        <td class="px-4 py-2 text-slate-500 dark:text-slate-400" dir="ltr">{{ $row['current'] ? $pairsText($row['current']->pairs) : '' }}</td>
                        <td class="px-4 py-2">
                            <span dir="ltr">{{ $pairsText($row['merged']->pairs) }}</span>
                            @if (! $row['merged']->isWorkDay)
                                <span class="ms-2 rounded bg-slate-100 dark:bg-slate-800 px-1.5 py-0.5 text-xs text-slate-600 dark:text-slate-300">{{ $row['merged']->note ?? 'غیرکاری' }}</span>
                            @endif
                            @if ($row['merged']->hasIncompletePair())
                                <span class="ms-2 rounded bg-amber-100 dark:bg-amber-900/40 px-1.5 py-0.5 text-xs text-amber-800 dark:text-amber-200">ناقص</span>
                            @endif
                        </td>
                        <td class="px-4 py-2 text-center" dir="ltr">{{ $row['merged']->workedMinutes() > 0 ? Duration::format($row['merged']->workedMinutes()) : '' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </section>
</x-layouts.app>
