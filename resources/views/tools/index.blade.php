<x-layouts.app title="ابزارها">
    <div class="mb-6">
        <h1 class="text-2xl font-bold">ابزارها</h1>
        <p class="mt-1 text-sm text-slate-500">ابزارهای کوچک روزمره. همه چیز در همین مرورگر انجام می‌شود و داده‌ای به جایی فرستاده نمی‌شود.</p>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        @foreach (config('tools.items') as $tool)
            <a href="{{ route($tool['route']) }}" class="group flex gap-4 rounded-xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-sky-300 hover:shadow-md">
                <span class="flex size-11 shrink-0 items-center justify-center rounded-lg bg-sky-50 text-sky-600 transition group-hover:bg-sky-100">
                    <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $tool['icon'] }}" /></svg>
                </span>
                <span>
                    <span class="block font-bold">{{ $tool['label'] }}</span>
                    <span class="mt-1 block text-sm leading-6 text-slate-500">{{ $tool['description'] }}</span>
                </span>
            </a>
        @endforeach
    </div>
</x-layouts.app>
