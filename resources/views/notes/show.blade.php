<x-layouts.app :title="$note->title">
    <x-slot:actions>
        <a href="{{ route('notes.edit', $note->id) }}" class="rounded-md bg-sky-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-sky-700">ویرایش</a>
    </x-slot:actions>

    <div class="mx-auto max-w-3xl">
        <a href="{{ route('notes.index') }}" class="text-sm text-slate-500 dark:text-slate-400 hover:text-sky-600 dark:hover:text-sky-400">&rarr; همه یادداشت‌ها</a>

        @if (session('status'))
            <div class="mt-4 rounded-lg border border-emerald-200 dark:border-emerald-800 bg-emerald-50 dark:bg-emerald-950/40 px-4 py-3 text-sm text-emerald-800 dark:text-emerald-200">{{ session('status') }}</div>
        @endif

        <article class="mt-4 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 p-6 shadow-sm lg:p-8">
            <header class="mb-6 border-b border-slate-100 dark:border-slate-800 pb-4">
                <h1 dir="{{ $note->titleDirection() }}" class="text-start text-2xl font-bold">{{ $note->title }}</h1>
                <p class="mt-2 flex items-center gap-2 text-xs text-slate-400 dark:text-slate-500">
                    @if ($note->isPinned)
                        <span class="rounded bg-amber-100 dark:bg-amber-900/40 px-1.5 py-0.5 text-amber-800 dark:text-amber-200">سنجاق‌شده</span>
                    @endif
                    آخرین تغییر: {{ $note->updatedAtLabel() }}
                </p>
            </header>

            @if (filled($note->body))
                <div class="markdown">{!! $note->bodyHtml() !!}</div>
            @else
                <p class="text-sm text-slate-400 dark:text-slate-500">این یادداشت متنی ندارد.</p>
            @endif
        </article>

        <form method="POST" action="{{ route('notes.destroy', $note->id) }}" data-confirm="این یادداشت حذف شود؟" class="mt-4 text-end">
            @csrf
            @method('DELETE')
            <button type="submit" class="rounded-md px-3 py-1.5 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-950/40">حذف یادداشت</button>
        </form>
    </div>
</x-layouts.app>
