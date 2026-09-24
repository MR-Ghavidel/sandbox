@props(['days', 'period'])

@use('App\Entities\AttendanceDayEntity')
@use('App\Support\Duration')
@use('App\Support\JalaliDate')
@use('Illuminate\Support\Number')

@php
    // Choosing one of these notes also turns off "work day" for that row.
    $noteOptions = ['جمعه' => true, 'تعطیل رسمی' => true, 'مرخصی' => false, 'مأموریت' => false, 'دورکاری' => false];
@endphp

<section data-attendance-table {{ $attributes->merge(['class' => 'rounded-xl border border-slate-200 bg-white shadow-sm']) }}>
    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 px-4 py-3">
        <h2 class="font-bold">ورود و خروج‌ها</h2>
        <p class="text-xs text-slate-500">
            تغییرات خودکار ذخیره می‌شوند. ساعت را مثل <span dir="ltr">08:30</span> یا <span dir="ltr">830</span> وارد کنید.
        </p>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-xs text-slate-500">
                <tr>
                    <th class="px-3 py-2 text-start font-medium">روز</th>
                    <th class="px-2 py-2 font-medium">کاری؟</th>
                    @foreach (range(1, AttendanceDayEntity::PAIRS_PER_DAY) as $pair)
                        <th class="px-1 py-2 font-medium">ورود {{ Number::format($pair, locale: 'fa') }}</th>
                        <th class="px-1 py-2 font-medium">خروج {{ Number::format($pair, locale: 'fa') }}</th>
                    @endforeach
                    <th class="px-2 py-2 font-medium">جمع</th>
                    <th class="px-3 py-2 text-start font-medium">توضیح</th>
                    <th class="w-8 px-1 py-2"><span class="sr-only">وضعیت ذخیره</span></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($days as $day)
                    @php($isCustomNote = $day->note !== null && ! array_key_exists($day->note, $noteOptions))
                    <tr
                        data-attendance-row
                        data-save-url="{{ route('payroll.days.update', ['year' => $period->year, 'month' => $period->month, 'date' => $day->date->toDateString()]) }}"
                        data-work-day="{{ $day->isWorkDay ? '1' : '0' }}"
                        @class([
                            'group/row',
                            'bg-sky-50/60' => $day->date->isToday(),
                            'data-[work-day=0]:bg-slate-50 data-[work-day=0]:text-slate-500' => ! $day->date->isToday(),
                        ])
                    >
                        <td class="px-3 py-1.5 whitespace-nowrap">
                            <span class="font-medium">{{ JalaliDate::format($day->date, 'EEEE') }}</span>
                            <span class="text-xs text-slate-500">{{ JalaliDate::format($day->date, 'd MMMM') }}</span>
                        </td>
                        <td class="px-2 py-1.5 text-center">
                            <input type="checkbox" data-field="is_work_day" @checked($day->isWorkDay) class="size-4 accent-sky-600" aria-label="روز کاری">
                        </td>
                        @foreach ($day->pairs as $index => $pair)
                            @foreach (['arrive', 'leave'] as $type)
                                <td class="px-1 py-1.5">
                                    <input
                                        type="text"
                                        inputmode="numeric"
                                        dir="ltr"
                                        maxlength="5"
                                        placeholder="--:--"
                                        data-time-input="{{ $type }}"
                                        data-field="{{ $type }}.{{ $index + 1 }}"
                                        value="{{ $pair[$type] }}"
                                        aria-label="{{ $type === 'arrive' ? 'ورود' : 'خروج' }} {{ $index + 1 }}"
                                        class="w-16 rounded border border-slate-200 bg-white px-1 py-1 text-center text-sm focus:border-sky-400 focus:outline-none aria-invalid:border-red-400 aria-invalid:bg-red-50"
                                    >
                                </td>
                            @endforeach
                        @endforeach
                        <td class="px-2 py-1.5 text-center font-medium whitespace-nowrap">
                            <span data-row-total dir="ltr">{{ $day->workedMinutes() > 0 ? Duration::format($day->workedMinutes()) : '' }}</span>
                            <span data-incomplete-badge @class(['block text-xs font-normal text-amber-600', 'hidden' => ! $day->hasIncompletePair()]) title="یک ورود یا خروج جا افتاده">ناقص</span>
                            <span data-off-day-work-badge @class(['block text-xs font-normal text-emerald-600', 'hidden' => $day->isWorkDay || $day->workedMinutes() === 0]) title="کار در روز غیرکاری کامل اضافه‌کار حساب می‌شود">اضافه‌کار</span>
                        </td>
                        <td class="px-3 py-1.5">
                            <div class="flex items-center gap-1">
                                <select data-note-select aria-label="توضیح" class="w-28 rounded border border-slate-200 bg-white px-1 py-1 text-sm focus:border-sky-400 focus:outline-none">
                                    <option value="">—</option>
                                    @foreach ($noteOptions as $note => $isDayOff)
                                        <option value="{{ $note }}" data-day-off="{{ $isDayOff ? '1' : '0' }}" @selected($day->note === $note)>{{ $note }}</option>
                                    @endforeach
                                    <option value="__other" @selected($isCustomNote)>سایر...</option>
                                </select>
                                <input type="text" data-note-other maxlength="255" value="{{ $isCustomNote ? $day->note : '' }}" placeholder="توضیح" aria-label="توضیح دلخواه" @class(['w-28 rounded border border-slate-200 bg-white px-2 py-1 text-sm focus:border-sky-400 focus:outline-none', 'hidden' => ! $isCustomNote])>
                            </div>
                        </td>
                        <td class="px-1 py-1.5 text-center">
                            {{-- Auto-save state: idle, saving, saved or error. --}}
                            <span data-save-state data-state="idle" class="inline-flex size-5 items-center justify-center rounded-full text-xs font-bold transition-opacity data-[state=error]:bg-red-100 data-[state=error]:text-red-600 data-[state=idle]:opacity-0 data-[state=saved]:bg-emerald-100 data-[state=saved]:text-emerald-600 data-[state=saving]:animate-pulse data-[state=saving]:text-slate-400"></span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</section>
