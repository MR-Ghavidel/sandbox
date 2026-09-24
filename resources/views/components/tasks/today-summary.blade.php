@props(['tasks'])

@use('App\Support\JalaliDate')
@use('App\TaskStatus')
@use('Illuminate\Support\Number')

@php
    $completedCount = $tasks->where('status', TaskStatus::Completed)->count();
    $progressPercent = $tasks->isEmpty() ? 0 : (int) round($completedCount / $tasks->count() * 100);
@endphp

<section data-today-summary data-collapsible="today-summary" class="group/collapsible mb-6 rounded-xl border border-sky-200 dark:border-sky-800 bg-gradient-to-l from-sky-50 dark:from-sky-950/40 to-white dark:to-slate-900 shadow-sm">
    <div class="flex flex-wrap items-center justify-between gap-3 px-4 py-3">
        <div>
            <h2 class="text-lg font-bold">کارهای امروز</h2>
            <p class="text-sm text-slate-500 dark:text-slate-400">{{ JalaliDate::format(today(), 'EEEE d MMMM y') }}</p>
        </div>

        <div class="flex items-center gap-3">
            <div class="text-sm text-slate-600 dark:text-slate-300">
                <span data-today-completed>{{ Number::format($completedCount, locale: 'fa') }}</span>
                از
                <span data-today-total>{{ Number::format($tasks->count(), locale: 'fa') }}</span>
                انجام شده
            </div>
            <div class="h-2 w-28 overflow-hidden rounded-full bg-slate-200 dark:bg-slate-700">
                <div data-today-progress class="h-full rounded-full bg-emerald-500 transition-all" style="width: {{ $progressPercent }}%"></div>
            </div>
            <x-collapse-toggle />
        </div>
    </div>

    <div data-collapse-body>
    <div class="min-h-0 overflow-hidden border-t border-sky-100 dark:border-sky-900/60 px-4 py-3">
        @if ($tasks->isEmpty())
            <p class="text-center text-sm text-slate-400 dark:text-slate-500">برای امروز کاری ثبت نشده</p>
        @else
            <ul class="grid gap-2 sm:grid-cols-2 xl:grid-cols-3">
                @foreach ($tasks as $task)
                    <li
                        data-task-id="{{ $task->id }}"
                        data-status="{{ $task->status->value }}"
                        data-update-url="{{ route('tasks.update', $task->id) }}"
                        data-task='@json($task->toDialogData())'
                        class="group flex cursor-pointer items-center gap-2 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-3 py-2 hover:border-slate-300 dark:hover:border-slate-600 hover:shadow-sm"
                    >
                        <span class="text-xs font-bold text-slate-400 dark:text-slate-500">{{ Number::format($loop->iteration, locale: 'fa') }}</span>
                        <span class="min-w-0 flex-1 truncate text-sm group-data-[status=completed]:text-slate-400 dark:group-data-[status=completed]:text-slate-500 group-data-[status=completed]:line-through">{{ $task->title }}</span>
                        <span data-status-badge class="shrink-0 rounded-full px-2 py-0.5 text-xs group-data-[status=completed]:bg-emerald-100 dark:group-data-[status=completed]:bg-emerald-900/40 group-data-[status=completed]:text-emerald-700 dark:group-data-[status=completed]:text-emerald-300 group-data-[status=in_progress]:bg-amber-100 dark:group-data-[status=in_progress]:bg-amber-900/40 group-data-[status=in_progress]:text-amber-700 dark:group-data-[status=in_progress]:text-amber-300 group-data-[status=not_started]:bg-slate-100 dark:group-data-[status=not_started]:bg-slate-800 group-data-[status=not_started]:text-slate-600 dark:group-data-[status=not_started]:text-slate-300">{{ $task->status->label() }}</span>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
    </div>
</section>
