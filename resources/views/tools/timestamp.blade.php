@php
    $inputClasses = 'w-full rounded-md border border-slate-200 bg-white px-2 py-1.5 text-center font-mono focus:border-sky-400 focus:outline-none';
    $dateFields = [['year', 'سال', 'w-20'], ['month', 'ماه', 'w-14'], ['day', 'روز', 'w-14'], ['hour', 'ساعت', 'w-14'], ['minute', 'دقیقه', 'w-14'], ['second', 'ثانیه', 'w-14']];
@endphp

<x-layouts.app title="تبدیل تایم‌استمپ">
    <div data-timestamp-tool>
        <div class="mb-4 flex flex-wrap items-end justify-between gap-4">
            <div>
                <a href="{{ route('tools.index') }}" class="text-sm text-sky-700 hover:underline">&rarr; ابزارها</a>
                <h1 class="mt-1 text-2xl font-bold">تبدیل تایم‌استمپ</h1>
                <p class="mt-1 text-sm text-slate-500">تایم‌استمپ یونیکس (ثانیه، میلی‌ثانیه یا میکروثانیه) ⇄ تاریخ و ساعت شمسی و میلادی</p>
            </div>

            <label class="flex items-center gap-2 text-sm">
                منطقه زمانی
                <select data-timezone class="rounded-md border border-slate-200 bg-white px-2 py-1.5">
                    <option value="Asia/Tehran">تهران</option>
                    <option value="UTC">UTC</option>
                    <option value="local">مرورگر من</option>
                </select>
            </label>
        </div>

        <section class="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-sky-200 bg-gradient-to-l from-sky-50 to-white px-5 py-4 shadow-sm">
            <div>
                <p class="text-sm text-slate-500">الان</p>
                <p class="font-mono text-2xl font-bold" dir="ltr" data-now-timestamp></p>
                <p class="text-sm text-slate-600" data-now-label></p>
            </div>
            <button type="button" data-copy-from="[data-now-timestamp]" class="rounded-md bg-sky-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-sky-700">کپی</button>
        </section>

        <div class="grid gap-4 lg:grid-cols-2">
            <section class="rounded-xl border border-slate-200 bg-white shadow-sm">
                <h2 class="border-b border-slate-100 px-4 py-3 font-bold">تایم‌استمپ ← تاریخ</h2>
                <div class="space-y-4 p-4">
                    <div class="flex gap-2">
                        <input type="text" inputmode="numeric" dir="ltr" data-timestamp-input placeholder="1758700000" class="{{ $inputClasses }} flex-1 text-lg">
                        <button type="button" data-action="use-now" class="rounded-md border border-slate-200 px-3 text-sm hover:bg-slate-50">الان</button>
                    </div>
                    <p data-timestamp-error class="hidden text-sm text-red-600"></p>

                    <dl data-timestamp-result class="hidden divide-y divide-slate-100 rounded-lg border border-slate-100 text-sm">
                        @foreach (['jalali' => 'شمسی', 'gregorian' => 'میلادی', 'iso' => 'ISO 8601 (UTC)', 'relative' => 'نسبت به الان', 'unit' => 'واحد تشخیص داده شده'] as $key => $label)
                            <div class="flex items-center justify-between gap-3 px-3 py-2">
                                <dt class="shrink-0 text-slate-500">{{ $label }}</dt>
                                <dd class="text-left font-medium" @if (in_array($key, ['gregorian', 'iso'])) dir="ltr" @endif data-result="{{ $key }}"></dd>
                            </div>
                        @endforeach
                    </dl>
                </div>
            </section>

            <section class="rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="flex items-center justify-between border-b border-slate-100 px-4 py-2.5">
                    <h2 class="font-bold">تاریخ ← تایم‌استمپ</h2>
                    <div class="flex overflow-hidden rounded-lg border border-slate-200 text-sm" role="radiogroup" aria-label="تقویم">
                        <label class="cursor-pointer px-3 py-1 has-checked:bg-sky-600 has-checked:text-white"><input type="radio" name="calendar" value="jalali" checked class="sr-only" data-calendar> شمسی</label>
                        <label class="cursor-pointer border-s border-slate-200 px-3 py-1 has-checked:bg-sky-600 has-checked:text-white"><input type="radio" name="calendar" value="gregorian" class="sr-only" data-calendar> میلادی</label>
                    </div>
                </div>
                <div class="space-y-4 p-4">
                    <div class="flex flex-wrap items-end gap-2">
                        @foreach ($dateFields as [$name, $label, $width])
                            <label class="{{ $width }} text-xs text-slate-500">
                                <span class="mb-1 block text-center">{{ $label }}</span>
                                <input type="text" inputmode="numeric" dir="ltr" data-date-part="{{ $name }}" class="{{ $inputClasses }}">
                            </label>
                        @endforeach
                        <button type="button" data-action="date-now" class="rounded-md border border-slate-200 px-3 py-1.5 text-sm hover:bg-slate-50">الان</button>
                    </div>
                    <p data-date-error class="hidden text-sm text-red-600"></p>

                    <dl data-date-result class="hidden divide-y divide-slate-100 rounded-lg border border-slate-100 text-sm">
                        @foreach (['seconds' => 'تایم‌استمپ (ثانیه)', 'milliseconds' => 'تایم‌استمپ (میلی‌ثانیه)'] as $key => $label)
                            <div class="flex items-center justify-between gap-3 px-3 py-2">
                                <dt class="text-slate-500">{{ $label }}</dt>
                                <dd class="flex items-center gap-2">
                                    <span class="font-mono font-medium" dir="ltr" data-result="{{ $key }}"></span>
                                    <button type="button" data-copy-from="[data-result='{{ $key }}']" class="rounded px-2 py-0.5 text-xs text-sky-700 hover:bg-sky-50">کپی</button>
                                </dd>
                            </div>
                        @endforeach
                        <div class="flex items-center justify-between gap-3 px-3 py-2">
                            <dt class="text-slate-500">معادل</dt>
                            <dd class="text-left" data-result="counterpart"></dd>
                        </div>
                    </dl>
                </div>
            </section>
        </div>
    </div>
</x-layouts.app>
