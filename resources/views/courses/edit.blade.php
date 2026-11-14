<x-layouts.app title="ویرایش درس">
    <div class="mx-auto max-w-2xl">
        <a href="{{ route('courses.show', $course->id) }}" class="text-sm text-slate-500 hover:text-sky-600 dark:text-slate-400 dark:hover:text-sky-400">&rarr; {{ $course->name }}</a>

        @if ($errors->any())
            <div class="mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-800 dark:bg-red-950/40 dark:text-red-300">
                <ul class="list-inside list-disc">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('courses.update', $course->id) }}" class="mt-4 space-y-4 rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
            @csrf
            @method('PUT')
            <x-courses.form :course="$course" />
            <div class="flex justify-end gap-2">
                <a href="{{ route('courses.show', $course->id) }}" class="rounded-md px-3 py-1.5 text-sm text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800">انصراف</a>
                <button type="submit" class="rounded-md bg-sky-600 px-4 py-1.5 text-sm font-medium text-white hover:bg-sky-700">ذخیره</button>
            </div>
        </form>

        <form method="POST" action="{{ route('courses.destroy', $course->id) }}" data-confirm="این درس و همه زمان‌ها و لینک‌هایش حذف شود؟" class="mt-4 text-end">
            @csrf
            @method('DELETE')
            <button type="submit" class="rounded-md px-3 py-1.5 text-sm text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-950/40">حذف درس</button>
        </form>
    </div>
</x-layouts.app>
