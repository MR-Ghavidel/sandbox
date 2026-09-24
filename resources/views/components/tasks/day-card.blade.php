@props(['date', 'tasks', 'statuses'])

@use('App\Support\JalaliDate')
@use('Illuminate\Support\Number')

@php($isToday = $date->isToday())

<section data-day data-collapsible="day-{{ $date->toDateString() }}" data-collapse-group="week" data-collapsed-by-default data-collapsed @class([
    'group/collapsible rounded-xl border bg-white shadow-sm',
    'border-sky-400 ring-2 ring-sky-100' => $isToday,
    'border-slate-200' => ! $isToday,
])>
    <div class="flex items-center justify-between gap-3 px-4 py-2.5">
        <h2 class="flex items-baseline gap-2">
            <span class="font-bold">{{ JalaliDate::format($date, 'EEEE') }}</span>
            <span class="text-sm text-slate-500">{{ JalaliDate::format($date, 'd MMMM y') }}</span>
            @if ($isToday)
                <span class="rounded bg-sky-100 px-1.5 py-0.5 text-xs font-medium text-sky-700">امروز</span>
            @endif
        </h2>
        <div class="flex items-center gap-2">
            <span data-count class="rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-600">{{ Number::format($tasks->count(), locale: 'fa') }}</span>
            <x-collapse-toggle />
        </div>
    </div>

    <div data-collapse-body class="border-t border-slate-100 group-data-[collapsed]/collapsible:hidden">
        <div class="px-3 pt-3">
            <p data-empty @class(['pb-1 text-center text-sm text-slate-400', 'hidden' => $tasks->isNotEmpty()])>کاری برای این روز ثبت نشده</p>

            <ol data-task-list data-date="{{ $date->toDateString() }}" class="min-h-10 space-y-2">
                @foreach ($tasks as $task)
                    <x-tasks.task-item :task="$task" :number="$loop->iteration" :statuses="$statuses" />
                @endforeach
            </ol>
        </div>

        <form method="POST" action="{{ route('tasks.store') }}" class="space-y-2 p-3">
            @csrf
            <input type="hidden" name="due_date" value="{{ $date->toDateString() }}">
            <div class="flex gap-2">
                <input type="text" name="title" required maxlength="255" placeholder="کار جدید..." class="min-w-0 flex-1 rounded-md border border-slate-200 px-2 py-1.5 text-sm focus:border-sky-400 focus:outline-none">
                <button type="submit" class="rounded-md bg-sky-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-sky-700">افزودن</button>
            </div>
            <label class="inline-flex cursor-pointer items-center gap-1.5 text-xs text-slate-500 select-none">
                <input type="checkbox" data-description-toggle class="size-3.5 accent-sky-600">
                توضیحات دارد
            </label>
            <textarea name="description" rows="3" maxlength="5000" disabled placeholder="توضیحات..." class="hidden w-full rounded-md border border-slate-200 px-2 py-1.5 text-sm focus:border-sky-400 focus:outline-none"></textarea>
        </form>
    </div>
</section>
