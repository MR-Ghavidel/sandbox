<x-layouts.app title="{{ $course->name }}">
    <a href="{{ route('courses.index') }}" class="text-sm text-slate-500 hover:text-sky-600 dark:text-slate-400 dark:hover:text-sky-400">&rarr; همه درس‌ها</a>

    @if (session('status'))
        <div class="mt-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-200">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-800 dark:bg-red-950/40 dark:text-red-300">
            <ul class="list-inside list-disc">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <header class="mt-4 rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900"
        style="border-inline-start: 5px solid {{ $course->colorHex() }}">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div class="min-w-0">
                <h1 class="text-2xl font-bold">{{ $course->name }}</h1>
                <div class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-sm text-slate-500 dark:text-slate-400">
                    @if (filled($course->instructor))
                        <span>👤 {{ $course->instructor }}</span>
                    @endif
                    @if (filled($course->location))
                        <span>📍 {{ $course->location }}</span>
                    @endif
                </div>
                @if (filled($course->notes))
                    <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">{{ $course->notes }}</p>
                @endif
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('courses.edit', $course->id) }}" class="rounded-lg border border-slate-200 px-3 py-1.5 text-sm hover:border-sky-300 hover:text-sky-600 dark:border-slate-700 dark:text-slate-300 dark:hover:border-sky-700 dark:hover:text-sky-400">ویرایش</a>
                <form method="POST" action="{{ route('courses.destroy', $course->id) }}" data-confirm="این درس و همه زمان‌ها و لینک‌هایش حذف شود؟">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="rounded-lg px-3 py-1.5 text-sm text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-950/40">حذف</button>
                </form>
            </div>
        </div>
    </header>

    <div class="mt-6 grid items-start gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <h2 class="text-lg font-bold">زمان کلاس‌ها</h2>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                            اگر ساعت کلاس در هفته‌های فرد و زوج متفاوت است، دو زمان جدا بسازید.
                        </p>
                    </div>
                    <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs dark:bg-slate-800">{{ \Illuminate\Support\Number::format($course->sessions->count(), locale: 'fa') }} زمان</span>
                </div>

                <div class="mt-4 space-y-3">
                    @forelse ($course->sessions as $session)
                        <div class="rounded-lg border border-slate-200 p-3 dark:border-slate-700">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="rounded-md bg-sky-50 px-2 py-1 text-sm font-medium text-sky-700 dark:bg-sky-950/40 dark:text-sky-300">{{ $session->dayName() }}</span>
                                    <span class="text-sm font-medium" dir="ltr">{{ $session->timeRange() }}</span>
                                    <span @class([
                                        'rounded-full px-2 py-0.5 text-[11px]',
                                        'bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300' => $session->meets_odd_weeks && ! $session->meets_even_weeks,
                                        'bg-violet-50 text-violet-700 dark:bg-violet-950/40 dark:text-violet-300' => $session->meets_even_weeks && ! $session->meets_odd_weeks,
                                        'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300' => $session->meets_odd_weeks && $session->meets_even_weeks,
                                    ])>{{ $session->weekLabel() }}</span>
                                </div>

                                <div class="flex items-center gap-1">
                                    <details class="group">
                                        <summary class="cursor-pointer list-none rounded-md p-1.5 text-slate-400 hover:bg-slate-100 hover:text-sky-600 dark:hover:bg-slate-800" title="ویرایش زمان">
                                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Z" /></svg>
                                        </summary>
                                        <form method="POST" action="{{ route('courses.sessions.update', [$course->id, $session->id]) }}" class="mt-3 space-y-3 rounded-lg bg-slate-50 p-3 dark:bg-slate-950/40">
                                            @csrf
                                            @method('PUT')
                                            <x-courses.session-fields :session="$session" :weekdays="$weekdays" />
                                            <div class="text-end">
                                                <button type="submit" class="rounded-md bg-sky-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-sky-700">ذخیره</button>
                                            </div>
                                        </form>
                                    </details>

                                    <form method="POST" action="{{ route('courses.sessions.destroy', [$course->id, $session->id]) }}" data-confirm="این زمان کلاس حذف شود؟">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="rounded-md p-1.5 text-slate-400 hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-950/40" title="حذف زمان" aria-label="حذف زمان">
                                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>
                                        </button>
                                    </form>
                                </div>
                            </div>

                            <x-alarms.list :alarmable="$session" :target="'session:'.$session->id" />
                        </div>
                    @empty
                        <div class="rounded-lg border border-dashed border-slate-300 p-6 text-center text-sm text-slate-400 dark:border-slate-700">
                            هنوز زمانی برای این درس ثبت نشده است.
                        </div>
                    @endforelse
                </div>

                <details class="group mt-4 rounded-lg border border-slate-200 dark:border-slate-700" @if ($course->sessions->isEmpty()) open @endif>
                    <summary class="flex cursor-pointer list-none items-center gap-2 px-3 py-2 text-sm font-medium text-sky-700 dark:text-sky-300">
                        <svg class="size-4 transition group-open:rotate-45" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                        افزودن زمان کلاس
                    </summary>
                    <form method="POST" action="{{ route('courses.sessions.store', $course->id) }}" class="space-y-3 border-t border-slate-100 p-3 dark:border-slate-800">
                        @csrf
                        <x-courses.session-fields :weekdays="$weekdays" />
                        <div class="text-end">
                            <button type="submit" class="rounded-md bg-sky-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-sky-700">افزودن زمان</button>
                        </div>
                    </form>
                </details>
            </section>

            <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-bold">کارها و تحقیق‌های این درس</h2>
                    <a href="{{ route('assignments.index') }}" class="text-sm text-sky-600 hover:text-sky-700 dark:text-sky-400">مدیریت کارها</a>
                </div>

                <div class="mt-3 space-y-2">
                    @forelse ($course->assignments as $assignment)
                        <a href="{{ route('assignments.index') }}#assignment-{{ $assignment->id }}"
                            class="flex flex-wrap items-center justify-between gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm transition hover:border-sky-300 dark:border-slate-700 dark:hover:border-sky-700">
                            <span class="flex items-center gap-2">
                                <span class="{{ $assignment->is_done ? 'text-emerald-500' : ($assignment->isOverdue() ? 'text-red-500' : 'text-slate-300') }}">{{ $assignment->is_done ? '✅' : '•' }}</span>
                                <span class="{{ $assignment->is_done ? 'text-slate-400 line-through' : '' }}">{{ $assignment->title }}</span>
                            </span>
                            <span class="text-xs text-slate-400">{{ $assignment->dueLabel() }}</span>
                        </a>
                    @empty
                        <p class="rounded-lg border border-dashed border-slate-300 p-4 text-center text-sm text-slate-400 dark:border-slate-700">کاری برای این درس ثبت نشده است.</p>
                    @endforelse
                </div>
            </section>
        </div>

        <aside class="space-y-6">
            <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
                <h2 class="text-lg font-bold">لینک‌ها</h2>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">گروه درس، منابع، جزوه و هر لینک دیگری.</p>

                <ul class="mt-4 space-y-2">
                    @forelse ($course->links as $link)
                        <li class="rounded-lg border border-slate-200 p-2.5 dark:border-slate-700">
                            <div class="flex items-start justify-between gap-2">
                                <a href="{{ $link->url }}" target="_blank" rel="noopener" dir="{{ \App\Support\TextDirection::detect($link->title) }}"
                                    class="min-w-0 flex-1 truncate text-sm font-medium text-sky-700 hover:underline dark:text-sky-400">{{ $link->title }}</a>

                                <div class="flex shrink-0 items-center gap-0.5">
                                    <details class="group">
                                        <summary class="cursor-pointer list-none rounded p-1 text-slate-400 hover:bg-slate-100 hover:text-sky-600 dark:hover:bg-slate-800" title="ویرایش لینک">
                                            <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Z" /></svg>
                                        </summary>
                                        <form method="POST" action="{{ route('courses.links.update', [$course->id, $link->id]) }}" class="mt-2 space-y-2">
                                            @csrf
                                            @method('PUT')
                                            <input type="text" name="title" value="{{ $link->title }}" required maxlength="255" data-auto-direction
                                                class="w-full rounded-md border border-slate-200 bg-white px-2 py-1 text-sm dark:border-slate-700 dark:bg-slate-900">
                                            <input type="url" name="url" value="{{ $link->url }}" required maxlength="2048" dir="ltr"
                                                class="w-full rounded-md border border-slate-200 bg-white px-2 py-1 text-sm dark:border-slate-700 dark:bg-slate-900">
                                            <button type="submit" class="rounded-md bg-sky-600 px-2.5 py-1 text-xs font-medium text-white hover:bg-sky-700">ذخیره</button>
                                        </form>
                                    </details>

                                    <form method="POST" action="{{ route('courses.links.destroy', [$course->id, $link->id]) }}" data-confirm="این لینک حذف شود؟">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="rounded p-1 text-slate-400 hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-950/40" title="حذف لینک" aria-label="حذف لینک">
                                            <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>
                                        </button>
                                    </form>
                                </div>
                            </div>
                            <p dir="ltr" class="mt-1 truncate text-end text-[11px] text-slate-400">{{ parse_url($link->url, PHP_URL_HOST) }}</p>
                        </li>
                    @empty
                        <li class="rounded-lg border border-dashed border-slate-300 p-4 text-center text-sm text-slate-400 dark:border-slate-700">لینکی ثبت نشده است.</li>
                    @endforelse
                </ul>

                <details class="group mt-4 rounded-lg border border-slate-200 dark:border-slate-700">
                    <summary class="flex cursor-pointer list-none items-center gap-2 px-3 py-2 text-sm font-medium text-sky-700 dark:text-sky-300">
                        <svg class="size-4 transition group-open:rotate-45" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                        افزودن لینک
                    </summary>
                    <form method="POST" action="{{ route('courses.links.store', $course->id) }}" class="space-y-2 border-t border-slate-100 p-3 dark:border-slate-800">
                        @csrf
                        <input type="text" name="title" value="{{ old('title') }}" required maxlength="255" placeholder="عنوان لینک" data-auto-direction
                            class="w-full rounded-md border border-slate-200 bg-white px-2 py-1.5 text-sm dark:border-slate-700 dark:bg-slate-900">
                        <input type="url" name="url" value="{{ old('url') }}" required maxlength="2048" dir="ltr" placeholder="https://..."
                            class="w-full rounded-md border border-slate-200 bg-white px-2 py-1.5 text-sm dark:border-slate-700 dark:bg-slate-900">
                        <button type="submit" class="w-full rounded-md bg-sky-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-sky-700">افزودن لینک</button>
                    </form>
                </details>
            </section>

            <section class="rounded-xl border border-sky-200 bg-sky-50/70 p-4 text-sm dark:border-sky-900 dark:bg-sky-950/30">
                <p class="font-medium text-sky-800 dark:text-sky-200">یادآوری با ربات بله</p>
                <p class="mt-1 text-xs leading-6 text-sky-700/80 dark:text-sky-300/80">
                    برای هر زمان کلاس می‌توانید چند یادآور بسازید (مثلاً ۱ روز، ۱ ساعت و ۳۰ دقیقه قبل). پیام‌ها توسط ربات بله ارسال می‌شوند.
                </p>
                <a href="{{ route('settings.edit') }}" class="mt-2 inline-block text-xs font-medium text-sky-700 hover:underline dark:text-sky-300">اتصال ربات و تنظیمات &larr;</a>
            </section>
        </aside>
    </div>
</x-layouts.app>
