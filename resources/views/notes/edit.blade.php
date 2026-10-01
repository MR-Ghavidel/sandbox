@use('App\Support\Markdown')
@use('App\Support\TextDirection')

@php
    $title = old('title', $note?->title);
    $body = old('body', $note?->body);
@endphp

<x-layouts.app :title="$note ? 'ویرایش یادداشت' : 'یادداشت جدید'">
    <form method="POST" action="{{ $note ? route('notes.update', $note->id) : route('notes.store') }}" data-note-editor data-preview-url="{{ route('notes.preview') }}" class="space-y-4">
        @csrf
        @if ($note)
            @method('PUT')
        @endif

        <div class="flex flex-wrap items-center justify-between gap-3">
            <a href="{{ $note ? route('notes.show', $note->id) : route('notes.index') }}" class="text-sm text-slate-500 dark:text-slate-400 hover:text-sky-600 dark:hover:text-sky-400">&rarr; انصراف</a>

            <div class="flex items-center gap-4">
                <label class="flex items-center gap-2 text-sm">
                    <input type="hidden" name="is_pinned" value="0">
                    <input type="checkbox" name="is_pinned" value="1" @checked(old('is_pinned', $note?->isPinned)) class="size-4 accent-sky-600">
                    سنجاق شود
                </label>
                <button type="submit" class="rounded-md bg-sky-600 px-4 py-1.5 text-sm font-medium text-white hover:bg-sky-700">ذخیره</button>
            </div>
        </div>

        @if ($errors->any())
            <div class="rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-950/40 px-4 py-3 text-sm text-red-700 dark:text-red-300">
                <ul class="list-inside list-disc">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- data-auto-direction: the field's direction follows its text while typing (resources/js/notes.js). --}}
        <input type="text" name="title" value="{{ $title }}" required maxlength="255" placeholder="عنوان یادداشت" aria-label="عنوان" data-auto-direction dir="{{ TextDirection::detect($title) }}"
            class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-4 py-3 text-lg font-bold focus:border-sky-400 dark:focus:border-sky-600 focus:outline-none">

        <div class="grid gap-4 lg:grid-cols-2">
            <section class="flex flex-col overflow-hidden rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900">
                <div class="flex items-center justify-between gap-3 border-b border-slate-100 dark:border-slate-800 px-4 py-2 text-xs text-slate-500 dark:text-slate-400">
                    <span class="font-medium">مارک‌داون</span>
                    <span dir="ltr" class="truncate"># heading · **bold** · - list · `code` · [link](url)</span>
                </div>
                <textarea name="body" rows="22" maxlength="100000" aria-label="متن" data-auto-direction data-note-body dir="{{ TextDirection::detect($body) }}"
                    class="min-h-[28rem] flex-1 resize-y bg-transparent px-4 py-3 text-sm leading-7 focus:outline-none">{{ $body }}</textarea>
            </section>

            <section class="flex flex-col overflow-hidden rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900">
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 px-4 py-2 text-xs text-slate-500 dark:text-slate-400">
                    <span class="font-medium">پیش‌نمایش</span>
                    <span data-preview-status></span>
                </div>
                <div data-note-preview class="markdown min-h-[28rem] flex-1 px-5 py-4">{!! Markdown::render($body) !!}</div>
            </section>
        </div>
    </form>
</x-layouts.app>
