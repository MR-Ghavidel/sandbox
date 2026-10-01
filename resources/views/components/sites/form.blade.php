@props(['site' => null])

{{-- Fields shared by "add site" (sites.index) and "edit site" (sites.edit). --}}
<div class="grid gap-3 sm:grid-cols-2">
    <label class="block text-sm">
        <span class="mb-1 block font-medium">آدرس</span>
        <input type="url" name="url" value="{{ old('url', $site?->url) }}" required maxlength="2048" dir="ltr" placeholder="https://example.com"
            class="w-full rounded-md border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-2 py-1.5 focus:border-sky-400 dark:focus:border-sky-600 focus:outline-none">
    </label>

    <label class="block text-sm">
        <span class="mb-1 block font-medium">عنوان <span class="font-normal text-slate-400">(خالی = نام دامنه)</span></span>
        <input type="text" name="title" value="{{ old('title', $site?->title) }}" maxlength="255" data-auto-direction
            class="w-full rounded-md border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-2 py-1.5 focus:border-sky-400 dark:focus:border-sky-600 focus:outline-none">
    </label>

    <label class="block text-sm sm:col-span-2">
        <span class="mb-1 block font-medium">توضیح</span>
        <input type="text" name="description" value="{{ old('description', $site?->description) }}" maxlength="500" data-auto-direction
            class="w-full rounded-md border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-2 py-1.5 focus:border-sky-400 dark:focus:border-sky-600 focus:outline-none">
    </label>

    <label class="flex items-center gap-2 text-sm">
        <input type="hidden" name="is_pinned" value="0">
        <input type="checkbox" name="is_pinned" value="1" @checked(old('is_pinned', $site?->isPinned)) class="size-4 accent-sky-600">
        سنجاق شود (همیشه اول فهرست)
    </label>
</div>
