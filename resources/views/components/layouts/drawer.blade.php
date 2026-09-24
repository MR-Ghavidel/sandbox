{{-- Dark overlay behind the drawer on mobile. --}}
<div data-drawer-overlay class="fixed inset-0 z-30 hidden bg-slate-900/40 group-data-[drawer=open]/body:block lg:group-data-[drawer=open]/body:hidden"></div>

<aside class="fixed inset-y-0 right-0 z-40 flex w-64 translate-x-full flex-col border-s border-slate-200 bg-white transition-transform duration-200 group-data-[drawer=open]/body:translate-x-0">
    <div class="flex h-14 items-center justify-between border-b border-slate-200 px-4">
        <span class="text-lg font-bold">{{ config('app.name') }}</span>
        <button type="button" data-drawer-close class="rounded-md p-1.5 text-slate-500 hover:bg-slate-100" aria-label="بستن منو">
            <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
        </button>
    </div>

    <nav class="flex-1 space-y-1 overflow-y-auto p-3">
        @foreach (config('navigation.items') as $item)
            @php($isActive = request()->routeIs($item['active']))
            <a href="{{ route($item['route']) }}" @class([
                'flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium',
                'bg-sky-50 text-sky-700' => $isActive,
                'text-slate-600 hover:bg-slate-100 hover:text-slate-900' => ! $isActive,
            ]) @if ($isActive) aria-current="page" @endif>
                <svg class="size-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}" /></svg>
                {{ $item['label'] }}
            </a>
        @endforeach
    </nav>
</aside>
