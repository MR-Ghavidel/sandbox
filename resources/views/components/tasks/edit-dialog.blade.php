@props(['statuses'])

<dialog id="edit-task-dialog" class="m-auto w-full max-w-md rounded-xl bg-white p-0 text-slate-800 shadow-xl backdrop:bg-slate-900/40 dark:bg-slate-900 dark:text-slate-100 dark:ring-1 dark:ring-white/10 dark:backdrop:bg-black/60">
    <form method="POST" class="space-y-4 p-5">
        @csrf
        @method('PATCH')
        <h2 class="text-lg font-bold">ویرایش کار</h2>

        <label class="block text-sm">
            <span class="mb-1 block font-medium">عنوان</span>
            <input type="text" name="title" required maxlength="255" class="w-full rounded-md border border-slate-200 dark:border-slate-700 px-2 py-1.5 focus:border-sky-400 dark:focus:border-sky-600 focus:outline-none">
        </label>

        <label class="block text-sm">
            <span class="mb-1 block font-medium">توضیحات</span>
            <textarea name="description" rows="4" maxlength="5000" class="w-full rounded-md border border-slate-200 dark:border-slate-700 px-2 py-1.5 focus:border-sky-400 dark:focus:border-sky-600 focus:outline-none"></textarea>
        </label>

        <label class="block text-sm">
            <span class="mb-1 block font-medium">وضعیت</span>
            <select name="status" class="w-full rounded-md border border-slate-200 dark:border-slate-700 px-2 py-1.5 focus:border-sky-400 dark:focus:border-sky-600 focus:outline-none">
                @foreach ($statuses as $status)
                    <option value="{{ $status->value }}">{{ $status->label() }}</option>
                @endforeach
            </select>
        </label>

        <div class="flex justify-end gap-2">
            <button type="button" data-close-dialog class="rounded-md px-3 py-1.5 text-sm text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800">انصراف</button>
            <button type="submit" class="rounded-md bg-sky-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-sky-700">ذخیره</button>
        </div>
    </form>
</dialog>
