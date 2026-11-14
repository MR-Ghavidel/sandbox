<x-layouts.app title="کارها و تحقیق‌ها">
    <div class="mb-6 flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold">کارها و تحقیق‌ها</h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">تحویل‌ها را با تاریخ شمسی ثبت کنید و چند یادآور برای هرکدام بسازید.</p>
        </div>
        <a href="{{ route('schedule.index') }}" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-600 hover:text-sky-600 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300">برنامه هفتگی</a>
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

    <details @class(['group mb-6 rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900']) @if ($errors->any() || $assignments->isEmpty()) open @endif>
        <summary class="flex cursor-pointer list-none items-center gap-2 px-5 py-3 text-sm font-medium text-sky-700 dark:text-sky-300">
            <svg class="size-4 transition group-open:rotate-45" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
            افزودن کار یا تحقیق
        </summary>

        <form method="POST" action="{{ route('assignments.store') }}" class="space-y-3 border-t border-slate-100 px-5 py-4 dark:border-slate-800">
            @csrf
            <x-assignments.form :courses="$courses" />
            <div class="text-end">
                <button type="submit" class="rounded-md bg-sky-600 px-4 py-1.5 text-sm font-medium text-white hover:bg-sky-700">افزودن</button>
            </div>
        </form>
    </details>

    <section>
        <h2 class="mb-3 text-lg font-bold">در انتظار</h2>

        @if ($assignments->isEmpty())
            <div class="rounded-xl border border-dashed border-slate-300 p-10 text-center text-sm text-slate-500 dark:border-slate-700 dark:text-slate-400">
                کار باز یا تحقیق جدیدی ندارید.
            </div>
        @else
            <div class="space-y-3">
                @foreach ($assignments as $assignment)
                    <article id="assignment-{{ $assignment->id }}" @class([
                        'rounded-xl border bg-white p-4 shadow-sm dark:bg-slate-900',
                        'border-red-200 dark:border-red-900' => $assignment->isOverdue(),
                        'border-slate-200 dark:border-slate-700' => ! $assignment->isOverdue(),
                    ])>
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h3 class="text-base font-bold">{{ $assignment->title }}</h3>
                                    @if ($assignment->isOverdue())
                                        <span class="rounded-full bg-red-100 px-2 py-0.5 text-[11px] font-medium text-red-700 dark:bg-red-950/50 dark:text-red-300">گذشته</span>
                                    @else
                                        <span class="rounded-full bg-sky-50 px-2 py-0.5 text-[11px] font-medium text-sky-700 dark:bg-sky-950/40 dark:text-sky-300">{{ $assignment->countdown() }}</span>
                                    @endif
                                </div>

                                <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-slate-500 dark:text-slate-400">
                                    @if ($assignment->course)
                                        <span class="flex items-center gap-1">
                                            <span class="size-2 rounded-full" style="background-color: {{ $assignment->course->colorHex() }}"></span>
                                            {{ $assignment->course->name }}
                                        </span>
                                    @endif
                                    <span>📅 {{ $assignment->dueLabel() }}</span>
                                </div>

                                @if (filled($assignment->description))
                                    <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">{{ $assignment->description }}</p>
                                @endif
                            </div>

                            <div class="flex items-center gap-1">
                                <form method="POST" action="{{ route('assignments.done', $assignment->id) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="rounded-md px-2.5 py-1.5 text-xs font-medium text-emerald-700 hover:bg-emerald-50 dark:text-emerald-300 dark:hover:bg-emerald-950/40">انجام شد</button>
                                </form>

                                <form method="POST" action="{{ route('assignments.destroy', $assignment->id) }}" data-confirm="این کار حذف شود؟">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="rounded-md p-1.5 text-slate-400 hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-950/40" title="حذف کار" aria-label="حذف کار">
                                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>
                                    </button>
                                </form>
                            </div>
                        </div>

                        <x-alarms.list :alarmable="$assignment" :target="'assignment:'.$assignment->id" />

                        <details class="group mt-3">
                            <summary class="cursor-pointer list-none text-xs font-medium text-sky-700 hover:underline dark:text-sky-300">ویرایش این کار</summary>
                            <form method="POST" action="{{ route('assignments.update', $assignment->id) }}" class="mt-3 space-y-3 rounded-lg bg-slate-50 p-3 dark:bg-slate-950/40">
                                @csrf
                                @method('PUT')
                                <x-assignments.form :assignment="$assignment" :courses="$courses" />
                                <div class="text-end">
                                    <button type="submit" class="rounded-md bg-sky-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-sky-700">ذخیره</button>
                                </div>
                            </form>
                        </details>
                    </article>
                @endforeach
            </div>
        @endif
    </section>

    @if ($doneAssignments->isNotEmpty())
        <details class="group mt-8 rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900">
            <summary class="flex cursor-pointer list-none items-center justify-between px-5 py-3">
                <span class="text-sm font-medium">انجام‌شده‌ها ({{ \Illuminate\Support\Number::format($doneAssignments->count(), locale: 'fa') }})</span>
                <svg class="size-4 text-slate-400 transition group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>
            </summary>
            <div class="divide-y divide-slate-100 border-t border-slate-100 dark:divide-slate-800 dark:border-slate-800">
                @foreach ($doneAssignments as $assignment)
                    <div id="assignment-{{ $assignment->id }}" class="flex flex-wrap items-center gap-3 px-5 py-2.5 text-sm">
                        <span class="text-emerald-500">✅</span>
                        <span class="min-w-0 flex-1 truncate text-slate-400 line-through">{{ $assignment->title }}</span>
                        <span class="text-xs text-slate-400">{{ $assignment->dueLabel() }}</span>
                        <form method="POST" action="{{ route('assignments.done', $assignment->id) }}">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="text-xs text-sky-600 hover:underline dark:text-sky-400">برگرداندن</button>
                        </form>
                        <form method="POST" action="{{ route('assignments.destroy', $assignment->id) }}" data-confirm="این کار حذف شود؟">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-xs text-red-500 hover:underline">حذف</button>
                        </form>
                    </div>
                @endforeach
            </div>
        </details>
    @endif
</x-layouts.app>
