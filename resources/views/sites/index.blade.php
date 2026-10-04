@use('Illuminate\Support\Number')

<x-layouts.app title="سایت‌ها">
    <div class="mb-6">
        <h1 class="text-2xl font-bold">سایت‌ها</h1>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
            سایت‌های محبوب و پرکاربرد. سایت‌های سنجاق‌شده اول می‌آیند و بعد هر سایتی که بیشتر از اینجا باز شده.
            برای آوردن سایت‌های پربازدید، تاریخچه و بوکمارک‌های مرورگر، در افزونه «ارسال سایت‌های مرورگر» را بزنید.
        </p>
    </div>

    @if (session('status'))
        <div class="mb-4 rounded-lg border border-emerald-200 dark:border-emerald-800 bg-emerald-50 dark:bg-emerald-950/40 px-4 py-3 text-sm text-emerald-800 dark:text-emerald-200">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="mb-4 rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-950/40 px-4 py-3 text-sm text-red-700 dark:text-red-300">
            <ul class="list-inside list-disc">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <details @class(['group mb-6 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 shadow-sm']) @if ($errors->any() || $sites->isEmpty()) open @endif>
        <summary class="flex cursor-pointer list-none items-center gap-2 px-5 py-3 text-sm font-medium text-sky-700 dark:text-sky-300">
            <svg class="size-4 transition group-open:rotate-45" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
            افزودن سایت
        </summary>

        <form method="POST" action="{{ route('sites.store') }}" class="space-y-3 border-t border-slate-100 dark:border-slate-800 px-5 py-4">
            @csrf
            <x-sites.form />
            <div class="text-end">
                <button type="submit" class="rounded-md bg-sky-600 px-4 py-1.5 text-sm font-medium text-white hover:bg-sky-700">افزودن</button>
            </div>
        </form>
    </details>

    @if ($sites->isEmpty())
        <div class="rounded-xl border border-dashed border-slate-300 dark:border-slate-700 p-10 text-center text-sm text-slate-500 dark:text-slate-400">هنوز سایتی ذخیره نشده است.</div>
    @else
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-4">
            @foreach ($sites as $site)
                <article class="group relative flex items-center gap-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 p-3 shadow-sm transition hover:border-sky-300 dark:hover:border-sky-700 hover:shadow-md">
                    {{-- The letter shows until (or unless) the site's logo loads over it. The logo is downloaded once and kept by the app. --}}
                    <span class="relative flex size-10 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-sky-50 dark:bg-sky-950/40 font-bold text-sky-700 dark:text-sky-300">
                        {{ $site->initial() }}
                        <img src="{{ route('sites.icon', ['site' => $site->id, 'v' => $site->iconCheckedAt?->timestamp]) }}" alt="" loading="lazy" onerror="this.remove()" class="absolute inset-0 m-auto size-7 object-contain bg-sky-50 dark:bg-sky-950/40">
                    </span>

                    <div class="min-w-0 flex-1">
                        {{-- The link covers the whole card; the buttons sit above it. It opens in a new tab and counts the visit. --}}
                        <a href="{{ route('sites.open', $site->id) }}" target="_blank" rel="noopener" dir="{{ $site->titleDirection() }}" class="block truncate text-start font-medium after:absolute after:inset-0">{{ $site->title }}</a>
                        <p dir="ltr" class="truncate text-end text-xs text-slate-400 dark:text-slate-500">{{ $site->host }}</p>
                        @if (filled($site->description))
                            <p dir="{{ $site->descriptionDirection() }}" class="mt-1 truncate text-start text-xs text-slate-500 dark:text-slate-400">{{ $site->description }}</p>
                        @endif
                    </div>

                    <div class="relative z-10 flex flex-col items-center gap-0.5">
                        <form method="POST" action="{{ route('sites.pin', $site->id) }}">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="is_pinned" value="{{ $site->isPinned ? 0 : 1 }}">
                            <button type="submit" title="{{ $site->isPinned ? 'برداشتن سنجاق' : 'سنجاق کردن' }}" aria-label="{{ $site->isPinned ? 'برداشتن سنجاق' : 'سنجاق کردن' }}" @class([
                                'rounded-md p-1 hover:bg-slate-100 dark:hover:bg-slate-800',
                                'text-amber-500' => $site->isPinned,
                                'text-slate-300 dark:text-slate-600 hover:text-slate-500' => ! $site->isPinned,
                            ])>
                                <svg class="size-4" viewBox="0 0 24 24" fill="{{ $site->isPinned ? 'currentColor' : 'none' }}" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.111a.563.563 0 0 0 .475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.562.562 0 0 0-.586 0L6.982 20.54a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.602a.562.562 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345L11.48 3.5Z" /></svg>
                            </button>
                        </form>
                        <a href="{{ route('sites.edit', $site->id) }}" title="ویرایش" aria-label="ویرایش" class="rounded-md p-1 text-slate-300 dark:text-slate-600 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-500">
                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Z" /></svg>
                        </a>
                    </div>

                    @if ($site->openCount > 0)
                        <span title="دفعات باز شدن از اینجا" class="absolute bottom-1.5 left-2 text-[10px] text-slate-300 dark:text-slate-600">{{ Number::format($site->openCount, locale: 'fa') }}×</span>
                    @endif
                </article>
            @endforeach
        </div>
    @endif
</x-layouts.app>
