<dialog id="show-task-dialog" class="m-auto w-full max-w-lg rounded-xl bg-white p-0 text-slate-800 shadow-xl backdrop:bg-slate-900/40 dark:bg-slate-900 dark:text-slate-100 dark:ring-1 dark:ring-white/10 dark:backdrop:bg-black/60">
    <div class="space-y-4 p-5">
        <div class="flex items-start justify-between gap-3">
            <h2 data-field="title" class="text-lg font-bold break-words"></h2>
            <span data-field="status_label" class="shrink-0 rounded-full bg-slate-100 dark:bg-slate-800 px-2.5 py-0.5 text-xs font-medium text-slate-700 dark:text-slate-200"></span>
        </div>

        <dl class="grid grid-cols-[auto_1fr] gap-x-4 gap-y-1 text-sm">
            <dt class="text-slate-500 dark:text-slate-400">روز</dt>
            <dd data-field="due_date_label"></dd>
            <dt class="text-slate-500 dark:text-slate-400">ایجاد</dt>
            <dd data-field="created_at_label"></dd>
            <dt class="text-slate-500 dark:text-slate-400">آخرین تغییر</dt>
            <dd data-field="updated_at_label"></dd>
        </dl>

        <div>
            <h3 class="mb-1 text-sm font-medium text-slate-500 dark:text-slate-400">توضیحات</h3>
            <p data-field="description" class="max-h-80 overflow-y-auto rounded-lg bg-slate-50 dark:bg-slate-800/60 p-3 text-sm leading-7 break-words whitespace-pre-line"></p>
        </div>

        <div class="flex justify-end gap-2">
            <button type="button" data-close-dialog class="rounded-md px-3 py-1.5 text-sm text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800">بستن</button>
            <button type="button" data-edit-from-details class="rounded-md bg-sky-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-sky-700">ویرایش</button>
        </div>
    </div>
</dialog>
