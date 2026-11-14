@props(['course' => null])

@php
    $colors = [
        'sky' => 'آبی آسمانی',
        'emerald' => 'سبز',
        'amber' => 'کهربایی',
        'rose' => 'صورتی',
        'violet' => 'بنفش',
        'teal' => 'فیروزه‌ای',
        'orange' => 'نارنجی',
        'indigo' => 'نیلی',
    ];
@endphp

<div class="grid gap-3 sm:grid-cols-2">
    <label class="block text-sm sm:col-span-2">
        <span class="mb-1 block font-medium">نام درس</span>
        <input type="text" name="name" value="{{ old('name', $course?->name) }}" required maxlength="255" data-auto-direction
            placeholder="مثلاً مبانی برنامه‌نویسی"
            class="w-full rounded-md border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-2 py-1.5 focus:border-sky-400 dark:focus:border-sky-600 focus:outline-none">
    </label>

    <label class="block text-sm">
        <span class="mb-1 block font-medium">استاد</span>
        <input type="text" name="instructor" value="{{ old('instructor', $course?->instructor) }}" maxlength="255" data-auto-direction
            class="w-full rounded-md border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-2 py-1.5 focus:border-sky-400 dark:focus:border-sky-600 focus:outline-none">
    </label>

    <label class="block text-sm">
        <span class="mb-1 block font-medium">مکان / کلاس</span>
        <input type="text" name="location" value="{{ old('location', $course?->location) }}" maxlength="255" data-auto-direction
            placeholder="مثلاً کلاس ۲۰۴"
            class="w-full rounded-md border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-2 py-1.5 focus:border-sky-400 dark:focus:border-sky-600 focus:outline-none">
    </label>

    <label class="block text-sm">
        <span class="mb-1 block font-medium">رنگ درس</span>
        <select name="color" class="w-full rounded-md border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-2 py-1.5 focus:border-sky-400 dark:focus:border-sky-600 focus:outline-none">
            @foreach ($colors as $value => $label)
                <option value="{{ $value }}" @selected(old('color', $course?->color ?? 'sky') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </label>

    <label class="block text-sm">
        <span class="mb-1 block font-medium">یادداشت <span class="font-normal text-slate-400">(اختیاری)</span></span>
        <input type="text" name="notes" value="{{ old('notes', $course?->notes) }}" maxlength="2000" data-auto-direction
            class="w-full rounded-md border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-2 py-1.5 focus:border-sky-400 dark:focus:border-sky-600 focus:outline-none">
    </label>
</div>
