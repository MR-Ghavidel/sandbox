@props(['days', 'period'])

@use('App\Entities\AttendanceDayEntity')
@use('App\Support\Duration')
@use('App\Support\JalaliDate')
@use('Illuminate\Support\Number')

<section {{ $attributes->merge(['class' => 'rounded-xl border border-slate-200 bg-white shadow-sm']) }}>
    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 px-4 py-3">
        <h2 class="font-bold">ورود و خروج‌ها</h2>
        <p class="text-xs text-slate-500">ساعت را مثل <span dir="ltr">08:30</span> یا <span dir="ltr">830</span> وارد کنید. دریافت خودکار از بیزاجی با افزونه کروم.</p>
    </div>

    <form method="POST" action="{{ route('payroll.days.update', ['year' => $period->year, 'month' => $period->month]) }}">
        @csrf
        @method('PUT')

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
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($days as $day)
                        @php
                            $date = $day->date->toDateString();
                            $isWorkDay = (bool) old("days.$date.is_work_day", $day->isWorkDay);
                        @endphp
                        <tr data-attendance-row @class([
                            'bg-sky-50/60' => $day->date->isToday(),
                            'bg-slate-50 text-slate-500' => ! $isWorkDay && ! $day->date->isToday(),
                        ])>
                            <td class="px-3 py-1.5 whitespace-nowrap">
                                <span class="font-medium">{{ JalaliDate::format($day->date, 'EEEE') }}</span>
                                <span class="text-xs text-slate-500">{{ JalaliDate::format($day->date, 'd MMMM') }}</span>
                            </td>
                            <td class="px-2 py-1.5 text-center">
                                <input type="hidden" name="days[{{ $date }}][is_work_day]" value="0">
                                <input type="checkbox" name="days[{{ $date }}][is_work_day]" value="1" @checked($isWorkDay) class="size-4 accent-sky-600" aria-label="روز کاری">
                            </td>
                            @foreach ($day->pairs as $index => $pair)
                                @foreach (['arrive', 'leave'] as $type)
                                    @php($field = "days.$date.$type.".($index + 1))
                                    <td class="px-1 py-1.5">
                                        <input
                                            type="text"
                                            inputmode="numeric"
                                            dir="ltr"
                                            maxlength="5"
                                            placeholder="--:--"
                                            data-time-input="{{ $type }}"
                                            name="days[{{ $date }}][{{ $type }}][{{ $index + 1 }}]"
                                            value="{{ old($field, $pair[$type]) }}"
                                            @class([
                                                'w-16 rounded border bg-white px-1 py-1 text-center text-sm focus:border-sky-400 focus:outline-none',
                                                'border-red-400' => $errors->has($field),
                                                'border-slate-200' => ! $errors->has($field),
                                            ])
                                        >
                                    </td>
                                @endforeach
                            @endforeach
                            <td class="px-2 py-1.5 text-center font-medium whitespace-nowrap" dir="ltr">
                                <span data-row-total>{{ $day->workedMinutes() > 0 ? Duration::format($day->workedMinutes()) : '' }}</span>
                                @if ($day->hasIncompletePair())
                                    <span class="block text-xs font-normal text-amber-600" title="یک ورود یا خروج جا افتاده">ناقص</span>
                                @endif
                            </td>
                            <td class="px-3 py-1.5">
                                <input type="text" list="attendance-notes" name="days[{{ $date }}][note]" value="{{ old("days.$date.note", $day->note) }}" maxlength="255" class="w-32 rounded border border-slate-200 bg-white px-2 py-1 text-sm focus:border-sky-400 focus:outline-none">
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <datalist id="attendance-notes">
            <option value="جمعه"></option>
            <option value="تعطیل رسمی"></option>
            <option value="مرخصی"></option>
            <option value="مأموریت"></option>
            <option value="دورکاری"></option>
        </datalist>

        <div class="flex justify-end border-t border-slate-100 p-3">
            <button type="submit" class="rounded-md bg-sky-600 px-4 py-2 text-sm font-medium text-white hover:bg-sky-700">ذخیره ورود و خروج‌ها</button>
        </div>
    </form>
</section>
