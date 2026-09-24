@use('App\Support\JalaliDate')
@use('App\TaskStatus')
@use('Illuminate\Support\Number')

@php($weekProgressPercent = $weekTotalCount === 0 ? 0 : (int) round($weekCompletedCount / $weekTotalCount * 100))

<x-layouts.app title="خانه">
    <div class="mb-6 rounded-xl border border-sky-200 dark:border-sky-800 bg-gradient-to-l from-sky-50 dark:from-sky-950/40 to-white dark:to-slate-900 p-5 shadow-sm">
        <h1 class="text-2xl font-bold">سلام، روز بخیر 👋</h1>
        <p class="mt-1 text-slate-600 dark:text-slate-300">امروز {{ JalaliDate::format(today(), 'EEEE d MMMM y') }}</p>
    </div>

    @if ($workHistory)
        <x-dashboard.work-history :history="$workHistory" />
    @else
        <p class="mb-6 rounded-xl border border-dashed border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-5 py-4 text-sm text-slate-500 dark:text-slate-400">
            وقتی ورود و خروج‌هایت را در <a href="{{ route('payroll.index') }}" class="text-sky-700 dark:text-sky-300 hover:underline">کارکرد و حقوق</a> ثبت کنی، اینجا کارنامه کاری‌ات (مدت همکاری، مجموع ساعت‌ها و دریافتی‌ها) نمایش داده می‌شود.
        </p>
    @endif

    <div class="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
        <div class="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 p-4 shadow-sm">
            <p class="text-sm text-slate-500 dark:text-slate-400">کارهای امروز</p>
            <p class="mt-1 text-2xl font-bold">{{ Number::format($todayTasks->count(), locale: 'fa') }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 p-4 shadow-sm">
            <p class="text-sm text-slate-500 dark:text-slate-400">{{ TaskStatus::Completed->label() }} امروز</p>
            <p class="mt-1 text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ Number::format($todayCountsByStatus[TaskStatus::Completed->value], locale: 'fa') }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 p-4 shadow-sm">
            <p class="text-sm text-slate-500 dark:text-slate-400">{{ TaskStatus::InProgress->label() }}</p>
            <p class="mt-1 text-2xl font-bold text-amber-600 dark:text-amber-400">{{ Number::format($todayCountsByStatus[TaskStatus::InProgress->value], locale: 'fa') }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 p-4 shadow-sm">
            <p class="text-sm text-slate-500 dark:text-slate-400">ناتمام از روزهای گذشته</p>
            <p @class(['mt-1 text-2xl font-bold', 'text-red-600 dark:text-red-400' => $unfinishedPastTasksCount > 0])>{{ Number::format($unfinishedPastTasksCount, locale: 'fa') }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 items-start gap-4 lg:grid-cols-2">
        <section class="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 shadow-sm">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 px-4 py-3">
                <h2 class="font-bold">کارهای امروز</h2>
                <a href="{{ route('tasks.index') }}" class="text-sm text-sky-700 dark:text-sky-300 hover:underline">مدیریت کارها &larr;</a>
            </div>

            @if ($todayTasks->isEmpty())
                <p class="p-6 text-center text-sm text-slate-400 dark:text-slate-500">برای امروز کاری ثبت نشده</p>
            @else
                <ul class="divide-y divide-slate-100 dark:divide-slate-800">
                    @foreach ($todayTasks as $task)
                        <li data-status="{{ $task->status->value }}" class="group flex items-center gap-3 px-4 py-2.5">
                            <span class="text-xs font-bold text-slate-400 dark:text-slate-500">{{ Number::format($loop->iteration, locale: 'fa') }}</span>
                            <span class="min-w-0 flex-1 truncate text-sm group-data-[status=completed]:text-slate-400 dark:group-data-[status=completed]:text-slate-500 group-data-[status=completed]:line-through">{{ $task->title }}</span>
                            <span class="shrink-0 rounded-full px-2 py-0.5 text-xs group-data-[status=completed]:bg-emerald-100 dark:group-data-[status=completed]:bg-emerald-900/40 group-data-[status=completed]:text-emerald-700 dark:group-data-[status=completed]:text-emerald-300 group-data-[status=in_progress]:bg-amber-100 dark:group-data-[status=in_progress]:bg-amber-900/40 group-data-[status=in_progress]:text-amber-700 dark:group-data-[status=in_progress]:text-amber-300 group-data-[status=not_started]:bg-slate-100 dark:group-data-[status=not_started]:bg-slate-800 group-data-[status=not_started]:text-slate-600 dark:group-data-[status=not_started]:text-slate-300">{{ $task->status->label() }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        <div class="space-y-4">
            <section class="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 p-4 shadow-sm">
                <div class="mb-2 flex items-center justify-between">
                    <h2 class="font-bold">پیشرفت این هفته</h2>
                    <span class="text-sm text-slate-600 dark:text-slate-300">
                        {{ Number::format($weekCompletedCount, locale: 'fa') }} از {{ Number::format($weekTotalCount, locale: 'fa') }}
                    </span>
                </div>
                <div class="h-3 overflow-hidden rounded-full bg-slate-200 dark:bg-slate-700">
                    <div class="h-full rounded-full bg-emerald-500" style="width: {{ $weekProgressPercent }}%"></div>
                </div>
                <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">{{ Number::format($weekProgressPercent, locale: 'fa') }}٪ کارهای هفته انجام شده</p>
            </section>

            <section class="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 p-4 shadow-sm">
                <h2 class="mb-3 font-bold">بخش‌ها</h2>
                <div class="grid gap-3 sm:grid-cols-2">
                    @foreach (config('navigation.items') as $item)
                        @continue($item['route'] === 'home')
                        <a href="{{ route($item['route']) }}" class="flex items-center gap-3 rounded-lg border border-slate-200 dark:border-slate-700 p-3 hover:border-sky-300 dark:hover:border-sky-700 hover:bg-sky-50 dark:hover:bg-sky-950/50">
                            <svg class="size-6 shrink-0 text-sky-600 dark:text-sky-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}" /></svg>
                            <span class="text-sm font-medium">{{ $item['label'] }}</span>
                        </a>
                    @endforeach
                </div>
            </section>
        </div>
    </div>
</x-layouts.app>
