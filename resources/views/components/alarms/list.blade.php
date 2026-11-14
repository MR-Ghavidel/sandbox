@props(['alarmable', 'target'])

@php
    $alarms = $alarmable->relationLoaded('alarms') ? $alarmable->alarms : $alarmable->alarms()->get();
    $presetId = 'alarm-presets-'.str_replace(':', '-', $target);
@endphp

<div class="mt-3 rounded-lg bg-slate-50 p-3 dark:bg-slate-950/40">
    <div class="flex items-center gap-2 text-sm font-medium text-slate-700 dark:text-slate-200">
        <svg class="size-4 text-amber-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" /></svg>
        یادآورها
        <span class="text-xs font-normal text-slate-400">با پیام ربات بله</span>
    </div>

    @if ($alarms->isNotEmpty())
        <ul class="mt-2 space-y-1.5">
            @foreach ($alarms as $alarm)
                <li class="flex flex-wrap items-center gap-2 rounded-md bg-white px-2 py-1.5 text-sm shadow-sm dark:bg-slate-900">
                    <span class="min-w-0 flex-1">{{ $alarm->label() }}</span>

                    <form method="POST" action="{{ route('alarms.update', $alarm->id) }}" class="flex items-center gap-1">
                        @csrf
                        @method('PATCH')
                        <input type="number" name="minutes_before" value="{{ $alarm->minutes_before }}" min="0" max="20160" required
                            title="دقیقه قبل از شروع" aria-label="دقیقه قبل از شروع"
                            class="w-16 rounded border border-slate-200 bg-white px-1.5 py-1 text-xs dark:border-slate-700 dark:bg-slate-900">
                        <button type="submit" title="ذخیره" aria-label="ذخیره" class="rounded p-1 text-sky-600 hover:bg-sky-50 dark:text-sky-400 dark:hover:bg-sky-950/40">
                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                        </button>
                    </form>

                    <form method="POST" action="{{ route('alarms.destroy', $alarm->id) }}" data-confirm="این یادآور حذف شود؟">
                        @csrf
                        @method('DELETE')
                        <button type="submit" title="حذف" aria-label="حذف" class="rounded p-1 text-slate-400 hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-950/40 dark:hover:text-red-400">
                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>
                        </button>
                    </form>
                </li>
            @endforeach
        </ul>
    @else
        <p class="mt-2 text-xs text-slate-400">هنوز یادآوری تنظیم نشده است.</p>
    @endif

    <form method="POST" action="{{ route('alarms.store') }}" class="mt-2 flex items-center gap-2">
        @csrf
        <input type="hidden" name="target" value="{{ $target }}">
        <input type="number" name="minutes_before" value="30" min="0" max="20160" required list="{{ $presetId }}"
            aria-label="دقیقه قبل از شروع" placeholder="دقیقه قبل"
            class="w-28 rounded-md border border-slate-200 bg-white px-2 py-1.5 text-sm dark:border-slate-700 dark:bg-slate-900">
        <datalist id="{{ $presetId }}">
            @foreach (\App\Support\AlarmInterval::PRESETS as $minutes)
                <option value="{{ $minutes }}">{{ \App\Support\AlarmInterval::label($minutes) }}</option>
            @endforeach
        </datalist>
        <button type="submit" class="rounded-md bg-slate-700 px-3 py-1.5 text-xs font-medium text-white hover:bg-slate-800 dark:bg-slate-200 dark:text-slate-900 dark:hover:bg-white">افزودن یادآور</button>
        <span class="text-xs text-slate-400">دقیقه قبل از شروع</span>
    </form>
</div>
