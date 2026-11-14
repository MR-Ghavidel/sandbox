@props(['assignment' => null, 'courses'])

<div class="grid gap-3 sm:grid-cols-2">
    <label class="block text-sm sm:col-span-2">
        <span class="mb-1 block font-medium">عنوان کار یا تحقیق</span>
        <input type="text" name="title" value="{{ old('title', $assignment?->title) }}" required maxlength="255" data-auto-direction
            placeholder="مثلاً حل تمرین فصل ۳"
            class="w-full rounded-md border border-slate-200 bg-white px-2 py-1.5 dark:border-slate-700 dark:bg-slate-900 focus:border-sky-400 dark:focus:border-sky-600 focus:outline-none">
    </label>

    <label class="block text-sm">
        <span class="mb-1 block font-medium">درس</span>
        <select name="course_id" class="w-full rounded-md border border-slate-200 bg-white px-2 py-1.5 dark:border-slate-700 dark:bg-slate-900 focus:border-sky-400 dark:focus:border-sky-600 focus:outline-none">
            <option value="">بدون درس</option>
            @foreach ($courses as $course)
                <option value="{{ $course->id }}" @selected((int) old('course_id', $assignment?->course_id) === $course->id)>{{ $course->name }}</option>
            @endforeach
        </select>
    </label>

    <label class="block text-sm">
        <span class="mb-1 block font-medium">ساعت تحویل</span>
        <input type="time" name="due_time" value="{{ old('due_time', $assignment ? $assignment->due_at->format('H:i') : '23:59') }}" required dir="ltr"
            class="w-full rounded-md border border-slate-200 bg-white px-2 py-1.5 dark:border-slate-700 dark:bg-slate-900 focus:border-sky-400 dark:focus:border-sky-600 focus:outline-none">
    </label>

    <div class="text-sm sm:col-span-2">
        <span class="mb-1 block font-medium">تاریخ تحویل (شمسی)</span>
        <x-jalali-date-input name="due_date" :value="$assignment?->due_at ?? now()->addWeek()" required />
    </div>

    <label class="block text-sm sm:col-span-2">
        <span class="mb-1 block font-medium">توضیح <span class="font-normal text-slate-400">(اختیاری)</span></span>
        <textarea name="description" rows="2" maxlength="5000" data-auto-direction
            class="w-full rounded-md border border-slate-200 bg-white px-2 py-1.5 dark:border-slate-700 dark:bg-slate-900 focus:border-sky-400 dark:focus:border-sky-600 focus:outline-none">{{ old('description', $assignment?->description) }}</textarea>
    </label>
</div>
