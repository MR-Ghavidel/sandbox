@php
    use App\Support\JalaliDate;

    $today = \Carbon\CarbonImmutable::now();
    $isCurrentWeek = $week['week_start']->isSameDay($today->startOfWeek(\Carbon\CarbonInterface::SATURDAY));
@endphp

<x-layouts.app title="برنامه هفتگی">
    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold">برنامه هفتگی</h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                {{ JalaliDate::long($week['week_start']) }} تا {{ JalaliDate::long($week['week_end']) }}
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <div class="flex items-center rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900">
                <a href="{{ route('schedule.index', ['week' => JalaliDate::input($previousWeek)]) }}" title="هفته قبل" aria-label="هفته قبل"
                    class="rounded-e-xl px-3 py-2 text-slate-500 hover:bg-slate-50 hover:text-sky-600 dark:text-slate-400 dark:hover:bg-slate-800">
                    <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" /></svg>
                </a>

                <div class="border-x border-slate-200 px-4 py-2 text-center dark:border-slate-700">
                    <div class="text-xs text-slate-400">هفته</div>
                    <div class="text-lg font-bold leading-tight">{{ \Illuminate\Support\Number::format($week['week_number'], locale: 'fa') }}</div>
                </div>

                <a href="{{ route('schedule.index', ['week' => JalaliDate::input($nextWeek)]) }}" title="هفته بعد" aria-label="هفته بعد"
                    class="rounded-s-xl px-3 py-2 text-slate-500 hover:bg-slate-50 hover:text-sky-600 dark:text-slate-400 dark:hover:bg-slate-800">
                    <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" /></svg>
                </a>
            </div>

            @if ($week['is_odd'])
                <span class="rounded-full bg-sky-100 px-3 py-1 text-sm font-bold text-sky-700 dark:bg-sky-950/50 dark:text-sky-300">هفته فرد</span>
            @else
                <span class="rounded-full bg-violet-100 px-3 py-1 text-sm font-bold text-violet-700 dark:bg-violet-950/50 dark:text-violet-300">هفته زوج</span>
            @endif

            @unless ($isCurrentWeek)
                <a href="{{ route('schedule.index') }}" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-600 hover:text-sky-600 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300">این هفته</a>
            @endunless

            <a href="{{ route('settings.edit') }}" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-600 hover:text-sky-600 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300">تنظیم هفته</a>
        </div>
    </div>

    @if (session('status'))
        <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-200">{{ session('status') }}</div>
    @endif

    @if ($occurrences->isEmpty())
        <div class="mb-6 rounded-xl border border-dashed border-slate-300 bg-white p-8 text-center dark:border-slate-700 dark:bg-slate-900">
            <div class="mx-auto flex size-14 items-center justify-center rounded-full bg-sky-50 text-sky-600 dark:bg-sky-950/40 dark:text-sky-300">
                <svg class="size-7" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" /></svg>
            </div>
            <p class="mt-3 font-medium">این هفته کلاسی ثبت نشده است.</p>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">درس‌ها و زمان کلاس‌ها را اضافه کنید تا برنامه اینجا نمایش داده شود.</p>
            <a href="{{ route('courses.index') }}" class="mt-4 inline-block rounded-lg bg-sky-600 px-4 py-2 text-sm font-medium text-white hover:bg-sky-700">مدیریت درس‌ها</a>
        </div>
    @endif

    <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-7">
        @for ($day = 0; $day <= 6; $day++)
            @php
                $dayDate = $week['week_start']->addDays($day);
                $dayOccurrences = $occurrencesByDay->get($day, collect());
                $isToday = $dayDate->isToday();
            @endphp

            <section @class([
                'flex min-h-40 flex-col overflow-hidden rounded-xl border bg-white shadow-sm dark:bg-slate-900',
                'border-sky-300 ring-2 ring-sky-100 dark:border-sky-700 dark:ring-sky-950/60' => $isToday,
                'border-slate-200 dark:border-slate-700' => ! $isToday,
            ])>
                <header @class([
                    'flex items-center justify-between gap-2 border-b px-3 py-2',
                    'border-sky-200 bg-sky-50/70 dark:border-sky-800 dark:bg-sky-950/30' => $isToday,
                    'border-slate-100 dark:border-slate-800' => ! $isToday,
                ])>
                    <div>
                        <div class="text-sm font-bold">{{ JalaliDate::weekdayName($day) }}</div>
                        <div class="text-[11px] text-slate-400">{{ JalaliDate::format($dayDate, 'd MMMM') }}</div>
                    </div>
                    @if ($isToday)
                        <span class="rounded-full bg-sky-600 px-2 py-0.5 text-[10px] font-bold text-white">امروز</span>
                    @endif
                </header>

                <div class="flex flex-1 flex-col gap-2 p-2">
                    @forelse ($dayOccurrences as $occurrence)
                        @php($session = $occurrence['session'])
                        <a href="{{ route('courses.show', $session->course_id) }}"
                            class="group block rounded-lg border border-slate-200 bg-slate-50 p-2.5 transition hover:border-sky-300 hover:bg-white hover:shadow-md dark:border-slate-700 dark:bg-slate-800/70 dark:hover:border-sky-700 dark:hover:bg-slate-800"
                            style="border-inline-start: 4px solid {{ $session->course->colorHex() }}">
                            <div class="truncate text-sm font-bold">{{ $session->course->name }}</div>
                            <div class="mt-1 flex items-center gap-1 text-xs text-slate-500 dark:text-slate-400" dir="ltr">
                                <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                                {{ $session->timeRange() }}
                            </div>
                            @if (filled($session->course->location))
                                <div class="mt-1 truncate text-[11px] text-slate-400">📍 {{ $session->course->location }}</div>
                            @endif
                            @if (filled($session->course->instructor))
                                <div class="mt-0.5 truncate text-[11px] text-slate-400">👤 {{ $session->course->instructor }}</div>
                            @endif
                        </a>
                    @empty
                        <div class="flex flex-1 items-center justify-center rounded-lg border border-dashed border-slate-200 p-3 text-center text-[11px] text-slate-300 dark:border-slate-700 dark:text-slate-600">
                            کلاسی نیست
                        </div>
                    @endforelse
                </div>
            </section>
        @endfor
    </div>

    @if ($assignments->isNotEmpty())
        <section class="mt-8">
            <div class="mb-3 flex items-center justify-between">
                <h2 class="text-lg font-bold">تحویل‌های این هفته</h2>
                <a href="{{ route('assignments.index') }}" class="text-sm text-sky-600 hover:text-sky-700 dark:text-sky-400">همه کارها &larr;</a>
            </div>

            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($assignments as $assignment)
                    <a href="{{ route('assignments.index') }}#assignment-{{ $assignment->id }}"
                        class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm transition hover:border-sky-300 hover:shadow-md dark:border-slate-700 dark:bg-slate-900 dark:hover:border-sky-700">
                        <div class="flex items-center gap-2">
                            @if ($assignment->course)
                                <span class="size-2.5 shrink-0 rounded-full" style="background-color: {{ $assignment->course->colorHex() }}"></span>
                            @endif
                            <span class="truncate font-medium">{{ $assignment->title }}</span>
                        </div>
                        <div class="mt-2 text-xs text-slate-500 dark:text-slate-400">📅 {{ $assignment->dueLabel() }}</div>
                        <div class="mt-1 text-xs {{ $assignment->isOverdue() ? 'text-red-500' : 'text-slate-400' }}">{{ $assignment->countdown() }}</div>
                    </a>
                @endforeach
            </div>
        </section>
    @endif
</x-layouts.app>
