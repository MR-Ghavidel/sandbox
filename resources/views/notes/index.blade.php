<x-layouts.app title="یادداشت‌ها">
    <x-slot:actions>
        <a href="{{ route('notes.create') }}" class="rounded-md bg-sky-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-sky-700">یادداشت جدید</a>
    </x-slot:actions>

    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold">یادداشت‌ها</h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">با مارک‌داون بنویسید؛ هر پاراگراف فارسی یا انگلیسی در جهت درست خودش نمایش داده می‌شود.</p>
        </div>

        <form method="GET" action="{{ route('notes.index') }}" class="flex items-center gap-2">
            <input type="search" name="q" value="{{ $search }}" placeholder="جستجو در یادداشت‌ها" aria-label="جستجو" class="w-64 rounded-md border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-3 py-1.5 text-sm focus:border-sky-400 dark:focus:border-sky-600 focus:outline-none">
            <button type="submit" class="rounded-md border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-3 py-1.5 text-sm hover:bg-slate-50 dark:hover:bg-slate-800">جستجو</button>
        </form>
    </div>

    @if (session('status'))
        <div class="mb-4 rounded-lg border border-emerald-200 dark:border-emerald-800 bg-emerald-50 dark:bg-emerald-950/40 px-4 py-3 text-sm text-emerald-800 dark:text-emerald-200">{{ session('status') }}</div>
    @endif

    @if ($notes->isEmpty())
        <div class="rounded-xl border border-dashed border-slate-300 dark:border-slate-700 p-10 text-center text-sm text-slate-500 dark:text-slate-400">
            @if (filled($search))
                یادداشتی با «{{ $search }}» پیدا نشد.
            @else
                هنوز یادداشتی ننوشته‌اید. <a href="{{ route('notes.create') }}" class="font-medium text-sky-600 dark:text-sky-400 hover:underline">اولین یادداشت را بنویسید</a>.
            @endif
        </div>
    @else
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($notes as $note)
                <article class="relative flex flex-col rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 p-5 shadow-sm transition hover:border-sky-300 dark:hover:border-sky-700 hover:shadow-md">
                    <div class="flex items-start gap-2">
                        <h2 dir="{{ $note->titleDirection() }}" class="flex-1 text-start font-bold">
                            {{-- The link covers the whole card; the pin button sits above it. --}}
                            <a href="{{ route('notes.show', $note->id) }}" class="after:absolute after:inset-0">{{ $note->title }}</a>
                        </h2>

                        <form method="POST" action="{{ route('notes.pin', $note->id) }}" class="relative z-10">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="is_pinned" value="{{ $note->isPinned ? 0 : 1 }}">
                            <button type="submit" title="{{ $note->isPinned ? 'برداشتن سنجاق' : 'سنجاق کردن' }}" aria-label="{{ $note->isPinned ? 'برداشتن سنجاق' : 'سنجاق کردن' }}" @class([
                                'rounded-md p-1 hover:bg-slate-100 dark:hover:bg-slate-800',
                                'text-amber-500' => $note->isPinned,
                                'text-slate-300 dark:text-slate-600 hover:text-slate-500' => ! $note->isPinned,
                            ])>
                                <svg class="size-5" viewBox="0 0 24 24" fill="{{ $note->isPinned ? 'currentColor' : 'none' }}" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.111a.563.563 0 0 0 .475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.562.562 0 0 0-.586 0L6.982 20.54a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.602a.562.562 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345L11.48 3.5Z" /></svg>
                            </button>
                        </form>
                    </div>

                    @if (filled($note->excerpt()))
                        <p dir="{{ $note->excerptDirection() }}" class="mt-2 line-clamp-3 flex-1 text-start text-sm leading-6 text-slate-600 dark:text-slate-300">{{ $note->excerpt() }}</p>
                    @endif

                    <p class="mt-3 text-xs text-slate-400 dark:text-slate-500">{{ $note->updatedAtLabel() }}</p>
                </article>
            @endforeach
        </div>
    @endif
</x-layouts.app>
