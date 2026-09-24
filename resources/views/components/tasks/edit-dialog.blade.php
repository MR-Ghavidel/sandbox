@props(['statuses'])

<dialog id="edit-task-dialog" class="m-auto w-full max-w-md rounded-xl p-0 shadow-xl backdrop:bg-slate-900/40">
    <form method="POST" class="space-y-4 p-5">
        @csrf
        @method('PATCH')
        <h2 class="text-lg font-bold">ویرایش کار</h2>

        <label class="block text-sm">
            <span class="mb-1 block font-medium">عنوان</span>
            <input type="text" name="title" required maxlength="255" class="w-full rounded-md border border-slate-200 px-2 py-1.5 focus:border-sky-400 focus:outline-none">
        </label>

        <label class="block text-sm">
            <span class="mb-1 block font-medium">توضیحات</span>
            <textarea name="description" rows="4" maxlength="5000" class="w-full rounded-md border border-slate-200 px-2 py-1.5 focus:border-sky-400 focus:outline-none"></textarea>
        </label>

        <label class="block text-sm">
            <span class="mb-1 block font-medium">وضعیت</span>
            <select name="status" class="w-full rounded-md border border-slate-200 px-2 py-1.5 focus:border-sky-400 focus:outline-none">
                @foreach ($statuses as $status)
                    <option value="{{ $status->value }}">{{ $status->label() }}</option>
                @endforeach
            </select>
        </label>

        <div class="flex justify-end gap-2">
            <button type="button" data-close-dialog class="rounded-md px-3 py-1.5 text-sm text-slate-600 hover:bg-slate-100">انصراف</button>
            <button type="submit" class="rounded-md bg-sky-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-sky-700">ذخیره</button>
        </div>
    </form>
</dialog>
