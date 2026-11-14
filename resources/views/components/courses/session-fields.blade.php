@props(['session' => null, 'weekdays'])

<div class="grid gap-3 sm:grid-cols-2">
    <label class="block text-sm">
        <span class="mb-1 block font-medium">روز هفته</span>
        <select name="day_of_week" required class="w-full rounded-md border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-2 py-1.5 focus:border-sky-400 dark:focus:border-sky-600 focus:outline-none">
            @foreach ($weekdays as $index => $weekday)
                <option value="{{ $index }}" @selected((int) old('day_of_week', $session?->day_of_week ?? 0) === $index)>{{ $weekday }}</option>
            @endforeach
        </select>
    </label>

    <div class="grid grid-cols-2 gap-2">
        <label class="block text-sm">
            <span class="mb-1 block font-medium">شروع</span>
            <input type="time" name="starts_at" value="{{ old('starts_at', $session ? substr((string) $session->starts_at, 0, 5) : '08:00') }}" required dir="ltr"
                class="w-full rounded-md border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-2 py-1.5 focus:border-sky-400 dark:focus:border-sky-600 focus:outline-none">
        </label>

        <label class="block text-sm">
            <span class="mb-1 block font-medium">پایان <span class="font-normal text-slate-400">(اختیاری)</span></span>
            <input type="time" name="ends_at" value="{{ old('ends_at', $session?->endTime()) }}" dir="ltr"
                class="w-full rounded-md border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-2 py-1.5 focus:border-sky-400 dark:focus:border-sky-600 focus:outline-none">
        </label>
    </div>

    <div class="flex flex-wrap items-center gap-4 text-sm sm:col-span-2">
        <span class="font-medium">این کلاس در کدام هفته‌ها برگزار می‌شود؟</span>

        <label class="flex items-center gap-2">
            <input type="hidden" name="meets_odd_weeks" value="0">
            <input type="checkbox" name="meets_odd_weeks" value="1" @checked(old('meets_odd_weeks', $session?->meets_odd_weeks ?? true)) class="size-4 accent-sky-600">
            هفته‌های فرد
        </label>

        <label class="flex items-center gap-2">
            <input type="hidden" name="meets_even_weeks" value="0">
            <input type="checkbox" name="meets_even_weeks" value="1" @checked(old('meets_even_weeks', $session?->meets_even_weeks ?? true)) class="size-4 accent-sky-600">
            هفته‌های زوج
        </label>
    </div>
</div>
