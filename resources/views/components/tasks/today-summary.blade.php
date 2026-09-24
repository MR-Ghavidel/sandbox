@props(['tasks'])

@use('App\Support\JalaliDate')
@use('App\TaskStatus')
@use('Illuminate\Support\Number')

@php
    $completedCount = $tasks->where('status', TaskStatus::Completed)->count();
    $progressPercent = $tasks->isEmpty() ? 0 : (int) round($completedCount / $tasks->count() * 100);
@endphp

<section data-today-summary data-collapsible="today-summary" class="group/collapsible mb-6 rounded-xl border border-sky-200 bg-gradient-to-l from-sky-50 to-white shadow-sm">
    <div class="flex flex-wrap items-center justify-between gap-3 px-4 py-3">
        <div>
            <h2 class="text-lg font-bold">کارهای امروز</h2>
            <p class="text-sm text-slate-500">{{ JalaliDate::format(today(), 'EEEE d MMMM y') }}</p>
        </div>

        <div class="flex items-center gap-3">
            <div class="text-sm text-slate-600">
                <span data-today-completed>{{ Number::format($completedCount, locale: 'fa') }}</span>
                از
                <span data-today-total>{{ Number::format($tasks->count(), locale: 'fa') }}</span>
                انجام شده
            </div>
            <div class="h-2 w-28 overflow-hidden rounded-full bg-slate-200">
                <div data-today-progress class="h-full rounded-full bg-emerald-500 transition-all" style="width: {{ $progressPercent }}%"></div>
            </div>
            <x-collapse-toggle />
        </div>
    </div>

    <div data-collapse-body class="border-t border-sky-100 px-4 py-3 group-data-[collapsed]/collapsible:hidden">
        @if ($tasks->isEmpty())
            <p class="text-center text-sm text-slate-400">برای امروز کاری ثبت نشده</p>
        @else
            <ul class="grid gap-2 sm:grid-cols-2 xl:grid-cols-3">
                @foreach ($tasks as $task)
                    <li
                        data-task-id="{{ $task->id }}"
                        data-status="{{ $task->status->value }}"
                        data-update-url="{{ route('tasks.update', $task->id) }}"
                        data-task='@json($task->toDialogData())'
                        class="group flex cursor-pointer items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 hover:border-slate-300 hover:shadow-sm"
                    >
                        <span class="text-xs font-bold text-slate-400">{{ Number::format($loop->iteration, locale: 'fa') }}</span>
                        <span class="min-w-0 flex-1 truncate text-sm group-data-[status=completed]:text-slate-400 group-data-[status=completed]:line-through">{{ $task->title }}</span>
                        <span data-status-badge class="shrink-0 rounded-full px-2 py-0.5 text-xs group-data-[status=completed]:bg-emerald-100 group-data-[status=completed]:text-emerald-700 group-data-[status=in_progress]:bg-amber-100 group-data-[status=in_progress]:text-amber-700 group-data-[status=not_started]:bg-slate-100 group-data-[status=not_started]:text-slate-600">{{ $task->status->label() }}</span>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</section>
