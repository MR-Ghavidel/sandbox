@use('App\Support\TextDirection')
@use('Illuminate\Support\Number')

@php
    $sourceLabels = ['top_sites' => 'پربازدید', 'history' => 'تاریخچه', 'bookmarks' => 'بوکمارک'];
    $newCount = $sites->reject(fn (array $site): bool => $savedSites->has($site['host']))->count();
@endphp

<x-layouts.app title="بررسی سایت‌های مرورگر">
    <div class="mb-6">
        <a href="{{ route('sites.index') }}" class="text-sm text-slate-500 dark:text-slate-400 hover:text-sky-600 dark:hover:text-sky-400">&rarr; همه سایت‌ها</a>
        <h1 class="mt-2 text-2xl font-bold">بررسی سایت‌های مرورگر</h1>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
            {{ Number::format($sites->count(), locale: 'fa') }} سایت از مرورگر رسید که {{ Number::format($newCount, locale: 'fa') }} تای آن جدید است.
            سایت‌های پربازدید و بوکمارک‌ها از قبل انتخاب شده‌اند؛ هر کدام را که می‌خواهید نگه دارید.
        </p>
    </div>

    @if (session('status'))
        <div class="mb-4 rounded-lg border border-emerald-200 dark:border-emerald-800 bg-emerald-50 dark:bg-emerald-950/40 px-4 py-3 text-sm text-emerald-800 dark:text-emerald-200">{{ session('status') }}</div>
    @endif

    @error('hosts')
        <div class="mb-4 rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-950/40 px-4 py-3 text-sm text-red-700 dark:text-red-300">{{ $message }}</div>
    @enderror

    <form method="POST" action="{{ route('site-imports.apply', $import->id) }}" data-site-import>
        @csrf

        <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center overflow-hidden rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-sm">
                <button type="button" data-select-sites="all" class="px-3 py-1.5 hover:bg-slate-50 dark:hover:bg-slate-800">انتخاب همه</button>
                <button type="button" data-select-sites="none" class="border-s border-slate-200 dark:border-slate-700 px-3 py-1.5 hover:bg-slate-50 dark:hover:bg-slate-800">هیچ‌کدام</button>
            </div>

            @if ($import->isApplied())
                <span class="rounded-full bg-slate-100 dark:bg-slate-800 px-3 py-1 text-sm text-slate-600 dark:text-slate-300">اعمال شده</span>
            @else
                <button type="submit" class="rounded-md bg-sky-600 px-4 py-1.5 text-sm font-medium text-white hover:bg-sky-700">افزودن سایت‌های انتخاب‌شده</button>
            @endif
        </div>

        <section class="overflow-x-auto rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 shadow-sm">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 dark:bg-slate-800/60 text-xs text-slate-500 dark:text-slate-400">
                    <tr>
                        <th class="w-10 px-4 py-2"></th>
                        <th class="px-4 py-2 text-start font-medium">سایت</th>
                        <th class="px-4 py-2 text-start font-medium">منبع</th>
                        <th class="px-4 py-2 font-medium">بازدید</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @foreach ($sites as $site)
                        @php($savedSite = $savedSites->get($site['host']))
                        <tr @class(['text-slate-400 dark:text-slate-500' => $savedSite])>
                            <td class="px-4 py-2 text-center">
                                <input type="checkbox" name="hosts[]" value="{{ $site['host'] }}" aria-label="انتخاب {{ $site['host'] }}" class="size-4 accent-sky-600"
                                    @disabled($savedSite || $import->isApplied())
                                    @checked(! $savedSite && array_intersect($site['sources'], ['top_sites', 'bookmarks']) !== [])>
                            </td>
                            <td class="max-w-md px-4 py-2">
                                <span dir="{{ TextDirection::detect($site['title']) }}" class="block truncate text-start font-medium">{{ $site['title'] }}</span>
                                <span dir="ltr" class="block truncate text-end text-xs text-slate-400 dark:text-slate-500">{{ $site['url'] }}</span>
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap">
                                @if ($savedSite)
                                    <span class="rounded bg-slate-100 dark:bg-slate-800 px-1.5 py-0.5 text-xs">قبلاً ذخیره شده</span>
                                @else
                                    @foreach ($site['sources'] as $source)
                                        <span class="rounded bg-sky-50 dark:bg-sky-950/40 px-1.5 py-0.5 text-xs text-sky-700 dark:text-sky-300">{{ $sourceLabels[$source] ?? $source }}</span>
                                    @endforeach
                                @endif
                            </td>
                            <td class="px-4 py-2 text-center">{{ $site['visit_count'] !== null ? Number::format($site['visit_count'], locale: 'fa') : '' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>
    </form>
</x-layouts.app>
