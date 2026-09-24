@props(['task', 'number', 'statuses'])

@use('Illuminate\Support\Number')

<li
    data-task-id="{{ $task->id }}"
    data-status="{{ $task->status->value }}"
    data-update-url="{{ route('tasks.update', $task->id) }}"
    data-task='@json($task->toDialogData())'
    title="برای دیدن جزئیات کلیک کنید"
    class="group flex cursor-pointer items-center gap-2 rounded-lg border border-s-4 border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-2.5 py-2 hover:border-slate-300 dark:hover:border-slate-600 hover:shadow-sm data-[status=completed]:border-s-emerald-500 data-[status=completed]:bg-emerald-50/50 dark:data-[status=completed]:bg-emerald-950/30 data-[status=in_progress]:border-s-amber-400 data-[status=not_started]:border-s-slate-300 dark:data-[status=not_started]:border-s-slate-600"
>
    <button type="button" data-drag-handle class="shrink-0 cursor-grab touch-none text-slate-400 dark:text-slate-500 hover:text-slate-600 dark:hover:text-slate-300 active:cursor-grabbing" title="جابجایی">
        <svg class="size-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M7 4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Zm0 6a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Zm-1.5 7.5a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3ZM16 4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Zm-1.5 7.5a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3ZM16 16a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Z"/></svg>
    </button>
    <span data-task-number class="flex size-6 shrink-0 items-center justify-center rounded-full bg-slate-100 dark:bg-slate-800 text-xs font-bold text-slate-600 dark:text-slate-300">{{ Number::format($number, locale: 'fa') }}</span>

    <div class="min-w-0 flex-1">
        <p class="text-sm font-medium break-words group-data-[status=completed]:text-slate-400 dark:group-data-[status=completed]:text-slate-500 group-data-[status=completed]:line-through">{{ $task->title }}</p>
        @if ($task->description)
            <p class="mt-0.5 line-clamp-2 text-xs whitespace-pre-line text-slate-500 dark:text-slate-400">{{ $task->description }}</p>
        @endif
    </div>

    <select data-status-select aria-label="وضعیت" class="shrink-0 rounded-md border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/60 px-1.5 py-1 text-xs focus:border-sky-400 dark:focus:border-sky-600 focus:outline-none">
        @foreach ($statuses as $status)
            <option value="{{ $status->value }}" @selected($task->status === $status)>{{ $status->label() }}</option>
        @endforeach
    </select>

    <div class="flex shrink-0 items-center">
        <button type="button" data-edit-task class="rounded px-1.5 py-1 text-xs text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-sky-700 dark:hover:text-sky-300">ویرایش</button>
        <form method="POST" action="{{ route('tasks.destroy', $task->id) }}" data-confirm="این کار حذف شود؟">
            @csrf
            @method('DELETE')
            <button type="submit" class="rounded px-1.5 py-1 text-xs text-slate-500 dark:text-slate-400 hover:bg-red-50 dark:hover:bg-red-950/40 hover:text-red-600 dark:hover:text-red-400">حذف</button>
        </form>
    </div>
</li>
