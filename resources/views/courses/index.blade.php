<x-layouts.app title="درس‌ها">
    <div class="mb-6">
        <h1 class="text-2xl font-bold">درس‌ها</h1>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
            درس‌ها، زمان کلاس‌ها (فرد/زوج) و لینک‌های هر درس را اینجا مدیریت کنید.
        </p>
    </div>

    @if (session('status'))
        <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-200">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-800 dark:bg-red-950/40 dark:text-red-300">
            <ul class="list-inside list-disc">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <details @class(['group mb-6 rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900']) @if ($errors->any() || $courses->isEmpty()) open @endif>
        <summary class="flex cursor-pointer list-none items-center gap-2 px-5 py-3 text-sm font-medium text-sky-700 dark:text-sky-300">
            <svg class="size-4 transition group-open:rotate-45" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
            افزودن درس
        </summary>

        <form method="POST" action="{{ route('courses.store') }}" class="space-y-3 border-t border-slate-100 px-5 py-4 dark:border-slate-800">
            @csrf
            <x-courses.form />
            <div class="text-end">
                <button type="submit" class="rounded-md bg-sky-600 px-4 py-1.5 text-sm font-medium text-white hover:bg-sky-700">افزودن</button>
            </div>
        </form>
    </details>

    @if ($courses->isEmpty())
        <div class="rounded-xl border border-dashed border-slate-300 p-10 text-center text-sm text-slate-500 dark:border-slate-700 dark:text-slate-400">
            هنوز درسی اضافه نکرده‌اید.
        </div>
    @else
        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($courses as $course)
                <article class="group relative overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm transition hover:shadow-md dark:border-slate-700 dark:bg-slate-900"
                    style="border-top: 4px solid {{ $course->colorHex() }}">
                    <div class="p-4">
                        <div class="flex items-start gap-3">
                            <span class="mt-1 size-3 shrink-0 rounded-full" style="background-color: {{ $course->colorHex() }}"></span>
                            <div class="min-w-0 flex-1">
                                <a href="{{ route('courses.show', $course->id) }}" class="block truncate text-lg font-bold after:absolute after:inset-0">{{ $course->name }}</a>
                                <div class="mt-1 flex flex-wrap gap-x-3 gap-y-1 text-xs text-slate-500 dark:text-slate-400">
                                    @if (filled($course->instructor))
                                        <span>👤 {{ $course->instructor }}</span>
                                    @endif
                                    @if (filled($course->location))
                                        <span>📍 {{ $course->location }}</span>
                                    @endif
                                </div>
                            </div>

                            <a href="{{ route('courses.edit', $course->id) }}" class="relative z-10 rounded-md p-1.5 text-slate-400 hover:bg-slate-100 hover:text-sky-600 dark:hover:bg-slate-800" title="ویرایش" aria-label="ویرایش">
                                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Z" /></svg>
                            </a>
                        </div>

                        @if ($course->sessions->isNotEmpty())
                            <div class="mt-3 flex flex-wrap gap-1.5">
                                @foreach ($course->sessions as $session)
                                    <span class="rounded-md bg-slate-100 px-2 py-1 text-[11px] text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                                        {{ $session->dayName() }} {{ $session->startTime() }}
                                        @if ($session->meets_odd_weeks && ! $session->meets_even_weeks)
                                            <span class="text-amber-600 dark:text-amber-400">· فرد</span>
                                        @elseif ($session->meets_even_weeks && ! $session->meets_odd_weeks)
                                            <span class="text-violet-600 dark:text-violet-400">· زوج</span>
                                        @endif
                                    </span>
                                @endforeach
                            </div>
                        @else
                            <p class="mt-3 text-xs text-slate-400">هنوز زمانی ثبت نشده است.</p>
                        @endif

                        <div class="mt-4 flex items-center gap-3 border-t border-slate-100 pt-3 text-xs text-slate-400 dark:border-slate-800">
                            <span>{{ \Illuminate\Support\Number::format($course->links_count, locale: 'fa') }} لینک</span>
                            <span>{{ \Illuminate\Support\Number::format($course->sessions_count, locale: 'fa') }} زمان</span>
                            <span>{{ \Illuminate\Support\Number::format($course->assignments_count, locale: 'fa') }} کار</span>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    @endif
</x-layouts.app>
