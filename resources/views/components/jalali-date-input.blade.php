@props(['name' => 'date', 'value' => null, 'required' => false])

@php
    $date = $value === null
        ? \Carbon\CarbonImmutable::now()
        : \Carbon\CarbonImmutable::parse($value);
    $parts = \App\Support\JalaliDate::parts($date);
    $currentYear = \App\Support\JalaliDate::parts(\Carbon\CarbonImmutable::now())['year'];
    $selectedYear = (int) old($name.'_year', $parts['year']);
    $years = collect(range($currentYear - 2, $currentYear + 3))->push($selectedYear)->unique()->sort()->values();
@endphp

<div class="grid grid-cols-[1.2fr_1fr_1fr] gap-2" dir="rtl">
    <label class="block">
        <span class="mb-1 block text-xs text-slate-500 dark:text-slate-400">سال</span>
        <select name="{{ $name }}_year" @required($required) class="w-full rounded-md border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-2 py-1.5 text-sm focus:border-sky-400 dark:focus:border-sky-600 focus:outline-none">
            @foreach ($years as $year)
                <option value="{{ $year }}" @selected($selectedYear === (int) $year)>{{ $year }}</option>
            @endforeach
        </select>
    </label>

    <label class="block">
        <span class="mb-1 block text-xs text-slate-500 dark:text-slate-400">ماه</span>
        <select name="{{ $name }}_month" @required($required) class="w-full rounded-md border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-2 py-1.5 text-sm focus:border-sky-400 dark:focus:border-sky-600 focus:outline-none">
            @foreach (\App\Support\JalaliDate::monthNames() as $number => $monthName)
                <option value="{{ $number }}" @selected((int) old($name.'_month', $parts['month']) === $number)>{{ $monthName }}</option>
            @endforeach
        </select>
    </label>

    <label class="block">
        <span class="mb-1 block text-xs text-slate-500 dark:text-slate-400">روز</span>
        <select name="{{ $name }}_day" @required($required) class="w-full rounded-md border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-2 py-1.5 text-sm focus:border-sky-400 dark:focus:border-sky-600 focus:outline-none">
            @foreach (range(1, 31) as $day)
                <option value="{{ $day }}" @selected((int) old($name.'_day', $parts['day']) === $day)>{{ $day }}</option>
            @endforeach
        </select>
    </label>
</div>
