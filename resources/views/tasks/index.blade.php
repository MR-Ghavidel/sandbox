@use('App\Support\JalaliDate')
@use('Illuminate\Support\Number')

<x-layouts.app title="کارهای من">
    <div data-task-board data-order-url="{{ route('tasks.order') }}">
        <x-tasks.today-summary :tasks="$todayTasks" />

        <div class="mb-4 flex flex-wrap items-center justify-between gap-4">
            <div>
                <h2 class="text-lg font-bold">روزهای هفته</h2>
                <p class="text-sm text-slate-500">
                    {{ JalaliDate::format($weekStart, 'd MMMM') }} تا {{ JalaliDate::format($weekEnd, 'd MMMM y') }}
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                @if ($unfinishedPastTasksCount > 0)
                    <form method="POST" action="{{ route('tasks.carry-over') }}">
                        @csrf
                        <button type="submit" class="rounded-lg bg-amber-100 px-3 py-2 text-sm font-medium text-amber-800 hover:bg-amber-200">
                            انتقال {{ Number::format($unfinishedPastTasksCount, locale: 'fa') }} کار ناتمام گذشته به امروز
                        </button>
                    </form>
                @endif

                <div class="flex items-center overflow-hidden rounded-lg border border-slate-200 bg-white text-sm">
                    <button type="button" data-expand-all="week" class="px-3 py-2 hover:bg-slate-50">باز کردن همه</button>
                    <button type="button" data-collapse-all="week" class="border-s border-slate-200 px-3 py-2 hover:bg-slate-50">جمع کردن همه</button>
                </div>

                <nav class="flex items-center overflow-hidden rounded-lg border border-slate-200 bg-white text-sm">
                    <a href="{{ route('tasks.index', ['week' => $weekStart->subWeek()->toDateString()]) }}" class="px-3 py-2 hover:bg-slate-50">&rarr; هفته قبل</a>
                    <a href="{{ route('tasks.index') }}" class="border-x border-slate-200 px-3 py-2 font-medium hover:bg-slate-50">این هفته</a>
                    <a href="{{ route('tasks.index', ['week' => $weekStart->addWeek()->toDateString()]) }}" class="px-3 py-2 hover:bg-slate-50">هفته بعد &larr;</a>
                </nav>
            </div>
        </div>

        @if ($errors->any())
            <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                <ul class="list-inside list-disc">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Two stacked columns on wide screens: Saturday–Tuesday, then Wednesday–Friday. --}}
        <div class="grid grid-cols-1 items-start gap-4 lg:grid-cols-2">
            @foreach ([$days->take(4), $days->slice(4)] as $columnDays)
                <div class="space-y-4">
                    @foreach ($columnDays as $day)
                        <x-tasks.day-card :date="$day['date']" :tasks="$day['tasks']" :statuses="$statuses" />
                    @endforeach
                </div>
            @endforeach
        </div>
    </div>

    <x-slot:dialogs>
        <x-tasks.show-dialog />
        <x-tasks.edit-dialog :statuses="$statuses" />
    </x-slot:dialogs>
</x-layouts.app>
