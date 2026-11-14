@php
    use App\Support\JalaliDate;
@endphp

<x-layouts.app title="تنظیمات">
    <div class="mb-6">
        <h1 class="text-2xl font-bold">تنظیمات</h1>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">وضعیت هفته‌های فرد و زوج را دستی تعیین کنید و ربات بله را وصل کنید.</p>
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

    <div class="grid items-start gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            {{-- Current week --}}
            <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900">
                <div class="flex flex-wrap items-center justify-between gap-4 p-5">
                    <div>
                        <h2 class="text-lg font-bold">این هفته</h2>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                            هفته {{ \Illuminate\Support\Number::format($currentWeek['week_number'], locale: 'fa') }}
                            &middot;
                            {{ JalaliDate::long($currentWeek['week_start']) }} تا {{ JalaliDate::long($currentWeek['week_end']) }}
                        </p>
                    </div>

                    <div @class([
                        'flex size-20 flex-col items-center justify-center rounded-2xl text-center',
                        'bg-sky-100 text-sky-700 dark:bg-sky-950/50 dark:text-sky-300' => $currentWeek['is_odd'],
                        'bg-violet-100 text-violet-700 dark:bg-violet-950/50 dark:text-violet-300' => ! $currentWeek['is_odd'],
                    ])>
                        <span class="text-2xl font-black">{{ $currentWeek['is_odd'] ? 'فرد' : 'زوج' }}</span>
                        <span class="text-[10px] opacity-70">هفته جاری</span>
                    </div>
                </div>

                <form method="POST" action="{{ route('settings.current-week') }}" class="border-t border-slate-100 bg-slate-50/60 p-5 dark:border-slate-800 dark:bg-slate-950/30">
                    @csrf
                    @method('PATCH')
                    <p class="text-sm font-medium">اگر این هفته به‌خاطر تعطیلی یا جابه‌جایی، فرد/زوج نیست، اینجا اصلاح کنید:</p>

                    <div class="mt-3 flex flex-wrap items-center gap-3">
                        <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-900">
                            <input type="radio" name="current_week_is_odd" value="1" @checked($currentWeek['is_odd']) class="size-4 accent-sky-600">
                            این هفته فرد است
                        </label>
                        <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-900">
                            <input type="radio" name="current_week_is_odd" value="0" @checked(! $currentWeek['is_odd']) class="size-4 accent-violet-600">
                            این هفته زوج است
                        </label>

                        <button type="submit" class="rounded-md bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-900 dark:bg-slate-200 dark:text-slate-900 dark:hover:bg-white">ذخیره وضعیت این هفته</button>
                    </div>

                    <p class="mt-2 text-xs leading-6 text-slate-400">
                        با تغییر این گزینه، وضعیت هفته‌های بعدی هم به‌صورت یک‌درمیان از همین هفته محاسبه می‌شود.
                    </p>
                </form>
            </section>

            {{-- Semester anchor --}}
            <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
                <h2 class="text-lg font-bold">مبنای ترم</h2>
                <p class="mt-1 text-xs leading-6 text-slate-500 dark:text-slate-400">
                    هفته‌ای که تاریخ شروع ترم در آن قرار دارد، «هفته اول» است. وضعیت فرد/زوج بقیه هفته‌ها از همین‌جا محاسبه می‌شود.
                </p>

                <form method="POST" action="{{ route('settings.update') }}" class="mt-4 space-y-4">
                    @csrf
                    @method('PUT')

                    <div>
                        <span class="mb-1 block text-sm font-medium">تاریخ شروع ترم (شمسی)</span>
                        <x-jalali-date-input name="semester_start" :value="$setting->semester_start_date" required />
                    </div>

                    <fieldset>
                        <legend class="mb-2 block text-sm font-medium">هفته اول ترم</legend>
                        <div class="flex flex-wrap gap-3">
                            <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-900">
                                <input type="radio" name="first_week_is_odd" value="1" @checked($setting->first_week_is_odd) class="size-4 accent-sky-600">
                                فرد
                            </label>
                            <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-900">
                                <input type="radio" name="first_week_is_odd" value="0" @checked(! $setting->first_week_is_odd) class="size-4 accent-violet-600">
                                زوج
                            </label>
                        </div>
                    </fieldset>

                    <button type="submit" class="rounded-md bg-sky-600 px-4 py-2 text-sm font-medium text-white hover:bg-sky-700">ذخیره تنظیمات ترم</button>
                </form>
            </section>

            {{-- Parity preview --}}
            <section class="rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900">
                <div class="border-b border-slate-100 px-5 py-3 dark:border-slate-800">
                    <h2 class="text-lg font-bold">پیش‌نمایش هفته‌ها</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50 text-xs text-slate-500 dark:bg-slate-800/60 dark:text-slate-400">
                            <tr>
                                <th class="px-4 py-2 text-start font-medium">هفته</th>
                                <th class="px-4 py-2 text-start font-medium">از</th>
                                <th class="px-4 py-2 text-start font-medium">تا</th>
                                <th class="px-4 py-2 text-start font-medium">وضعیت</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @foreach ($previewWeeks as $preview)
                                <tr @class(['bg-sky-50/60 dark:bg-sky-950/20' => $preview['week_start']->isSameDay($currentWeek['week_start'])])>
                                    <td class="px-4 py-2 font-medium">
                                        {{ \Illuminate\Support\Number::format($preview['week_number'], locale: 'fa') }}
                                        @if ($preview['week_start']->isSameDay($currentWeek['week_start']))
                                            <span class="ms-1 rounded bg-sky-600 px-1.5 py-0.5 text-[10px] text-white">جاری</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-2 text-slate-500 dark:text-slate-400">{{ JalaliDate::format($preview['week_start'], 'd MMMM') }}</td>
                                    <td class="px-4 py-2 text-slate-500 dark:text-slate-400">{{ JalaliDate::format($preview['week_end'], 'd MMMM') }}</td>
                                    <td class="px-4 py-2">
                                        <span @class([
                                            'rounded-full px-2 py-0.5 text-xs font-medium',
                                            'bg-sky-100 text-sky-700 dark:bg-sky-950/50 dark:text-sky-300' => $preview['is_odd'],
                                            'bg-violet-100 text-violet-700 dark:bg-violet-950/50 dark:text-violet-300' => ! $preview['is_odd'],
                                        ])>{{ $preview['is_odd'] ? 'فرد' : 'زوج' }}</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        </div>

        <aside class="space-y-6">
            <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
                <div class="flex items-center gap-2">
                    <span class="flex size-9 items-center justify-center rounded-lg bg-sky-50 text-sky-600 dark:bg-sky-950/40 dark:text-sky-300">
                        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z" /></svg>
                    </span>
                    <div>
                        <h2 class="text-lg font-bold">ربات بله</h2>
                        <p class="text-xs text-slate-400">دریافت برنامه، لینک‌ها و یادآورها</p>
                    </div>
                </div>

                @if (! $botConfigured)
                    <div class="mt-4 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs leading-6 text-amber-800 dark:border-amber-800 dark:bg-amber-950/40 dark:text-amber-200">
                        توکن ربات تنظیم نشده است. در فایل <code class="rounded bg-amber-100 px-1 dark:bg-amber-900">.env</code> مقدار
                        <code class="rounded bg-amber-100 px-1 dark:bg-amber-900">BALE_BOT_TOKEN</code> را از BotFather بله بگیرید و وارد کنید.
                    </div>
                @endif

                @if ($subscriber)
                    <div class="mt-4 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-3 text-sm dark:border-emerald-800 dark:bg-emerald-950/40">
                        <p class="font-medium text-emerald-800 dark:text-emerald-200">✅ ربات متصل است</p>
                        <p class="mt-1 text-xs text-emerald-700 dark:text-emerald-300">
                            {{ $subscriber->first_name ?: 'کاربر بله' }}
                            @if ($subscriber->username)
                                &middot; {{ '@'.$subscriber->username }}
                            @endif
                        </p>

                        <form method="POST" action="{{ route('settings.bale.destroy') }}" data-confirm="اتصال ربات قطع شود؟" class="mt-3">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-xs text-red-600 hover:underline dark:text-red-400">قطع اتصال</button>
                        </form>
                    </div>
                @else
                    @if ($linkCode)
                        <div class="mt-4 rounded-lg border border-sky-200 bg-sky-50 p-4 text-center dark:border-sky-900 dark:bg-sky-950/30">
                            <p class="text-xs text-slate-500 dark:text-slate-400">این کد را در ربات بفرستید:</p>
                            <p class="mt-1 select-all font-mono text-2xl font-bold tracking-widest text-sky-800 dark:text-sky-200" dir="ltr">{{ $linkCode->code }}</p>
                            <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">دستور:</p>
                            <code class="mt-1 inline-block select-all rounded bg-white px-2 py-1 text-xs text-slate-700 dark:bg-slate-900 dark:text-slate-200" dir="ltr">/start {{ $linkCode->code }}</code>
                            <p class="mt-2 text-[11px] text-slate-400">کد تا {{ JalaliDate::format($linkCode->expires_at, 'd MMMM، H:mm') }} معتبر است.</p>
                        </div>

                        <ol class="mt-3 list-inside list-decimal space-y-1 text-xs leading-6 text-slate-500 dark:text-slate-400">
                            <li>ربات {{ $botUsername ? '@'.$botUsername : '' }} را در بله باز کنید.</li>
                            <li>دستور بالا را بفرستید.</li>
                            <li>پیام تأیید اتصال را دریافت می‌کنید.</li>
                        </ol>
                    @endif

                    <form method="POST" action="{{ route('settings.bale-code') }}" class="mt-3">
                        @csrf
                        <button type="submit" class="w-full rounded-md bg-sky-600 px-3 py-2 text-sm font-medium text-white hover:bg-sky-700">
                            {{ $linkCode ? 'ساخت کد جدید' : 'دریافت کد اتصال' }}
                        </button>
                    </form>
                @endif
            </section>

            <section class="rounded-xl border border-slate-200 bg-white p-5 text-sm shadow-sm dark:border-slate-700 dark:bg-slate-900">
                <h2 class="font-bold">ارسال یادآورها</h2>
                <p class="mt-1 text-xs leading-6 text-slate-500 dark:text-slate-400">
                    زمان‌بند لاراول هر دقیقه یادآورهای سررسید را بررسی و از طریق ربات ارسال می‌کند.
                </p>

                <div class="mt-3 space-y-2 text-xs" dir="ltr">
                    <code class="block rounded bg-slate-100 px-2 py-1 dark:bg-slate-800">php artisan schedule:work</code>
                    <code class="block rounded bg-slate-100 px-2 py-1 dark:bg-slate-800">php artisan bale:poll</code>
                    <code class="block rounded bg-slate-100 px-2 py-1 dark:bg-slate-800">php artisan alarms:dispatch</code>
                </div>

                <p class="mt-3 text-[11px] leading-6 text-slate-400">
                    در سرور، به‌جای <span dir="ltr">bale:poll</span> می‌توانید وبهوک را با
                    <span dir="ltr">php artisan bale:webhook</span> ثبت کنید.
                </p>
            </section>
        </aside>
    </div>
</x-layouts.app>
