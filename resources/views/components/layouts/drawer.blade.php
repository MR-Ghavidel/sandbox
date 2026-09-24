{{-- Dark overlay behind the drawer on mobile. --}}
<div data-drawer-overlay class="fixed inset-0 z-30 hidden bg-slate-900/40 group-data-[drawer=open]/body:block lg:group-data-[drawer=open]/body:hidden"></div>

<aside class="fixed inset-y-0 right-0 z-40 flex w-64 translate-x-full flex-col border-s border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 transition-transform duration-200 group-data-[drawer=open]/body:translate-x-0">
    <div class="flex h-14 items-center justify-between border-b border-slate-200 dark:border-slate-700 px-4">
        <span class="text-lg font-bold">{{ config('app.name') }}</span>
        <button type="button" data-drawer-close class="rounded-md p-1.5 text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800" aria-label="بستن منو">
            <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
        </button>
    </div>

    {{-- Replaced on every soft navigation, so the active item follows the page. --}}
    <nav data-drawer-nav class="flex-1 space-y-1 overflow-y-auto p-3">
        @foreach (config('navigation.items') as $item)
            @php
                $isActive = request()->routeIs(...(array) $item['active']);
                $children = isset($item['children']) ? config($item['children'], []) : [];
                $hasActiveChild = collect($children)->contains(fn (array $child): bool => request()->routeIs($child['route']));
                $linkClasses = [
                    'flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium',
                    'bg-sky-50 dark:bg-sky-950/40 text-sky-700 dark:text-sky-300' => $isActive,
                    'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white' => ! $isActive,
                ];
            @endphp

            @if ($children === [])
                <a href="{{ route($item['route']) }}" @class($linkClasses) @if ($isActive) aria-current="page" @endif>
                    <svg class="size-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}" /></svg>
                    {{ $item['label'] }}
                </a>
            @else
                {{-- Item with a collapsible list of sub-items; opened automatically while one of them is active. --}}
                <div data-collapsible="nav-{{ $item['route'] }}" data-collapsed-by-default @if ($hasActiveChild) data-collapse-force-open @else data-collapsed @endif class="group/collapsible">
                    <div class="flex items-center gap-1">
                        <a href="{{ route($item['route']) }}" @class([...$linkClasses, 'flex-1']) @if (request()->routeIs($item['route'])) aria-current="page" @endif>
                            <svg class="size-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}" /></svg>
                            {{ $item['label'] }}
                        </a>
                        <button type="button" data-collapse-toggle aria-expanded="true" aria-label="نمایش زیرمجموعه‌های {{ $item['label'] }}" class="rounded-lg p-2 text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-800 dark:hover:text-slate-100">
                            <svg class="size-4 transition-transform duration-200 group-data-[collapsed]/collapsible:rotate-90" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>
                        </button>
                    </div>

                    <div data-collapse-body>
                        <div class="min-h-0 overflow-hidden">
                            <ul class="ms-5 mt-1 space-y-0.5 border-s border-slate-200 dark:border-slate-700 ps-3">
                                @foreach ($children as $child)
                                    @php($isChildActive = request()->routeIs($child['route']))
                                    <li>
                                        <a href="{{ route($child['route']) }}" @class([
                                            'block rounded-md px-3 py-1.5 text-sm',
                                            'bg-sky-50 dark:bg-sky-950/40 font-medium text-sky-700 dark:text-sky-300' => $isChildActive,
                                            'text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white' => ! $isChildActive,
                                        ]) @if ($isChildActive) aria-current="page" @endif>{{ $child['label'] }}</a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            @endif
        @endforeach
    </nav>
</aside>
