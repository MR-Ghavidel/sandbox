<x-layouts.app title="ویرایش سایت">
    <div class="mx-auto max-w-2xl">
        <a href="{{ route('sites.index') }}" class="text-sm text-slate-500 dark:text-slate-400 hover:text-sky-600 dark:hover:text-sky-400">&rarr; همه سایت‌ها</a>

        @if ($errors->any())
            <div class="mt-4 rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-950/40 px-4 py-3 text-sm text-red-700 dark:text-red-300">
                <ul class="list-inside list-disc">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('sites.update', $site->id) }}" class="mt-4 space-y-4 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 p-5 shadow-sm">
            @csrf
            @method('PUT')
            <x-sites.form :site="$site" />
            <div class="flex justify-end gap-2">
                <a href="{{ route('sites.index') }}" class="rounded-md px-3 py-1.5 text-sm text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800">انصراف</a>
                <button type="submit" class="rounded-md bg-sky-600 px-4 py-1.5 text-sm font-medium text-white hover:bg-sky-700">ذخیره</button>
            </div>
        </form>

        <form method="POST" action="{{ route('sites.destroy', $site->id) }}" data-confirm="این سایت حذف شود؟" class="mt-4 text-end">
            @csrf
            @method('DELETE')
            <button type="submit" class="rounded-md px-3 py-1.5 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-950/40">حذف سایت</button>
        </form>
    </div>
</x-layouts.app>
