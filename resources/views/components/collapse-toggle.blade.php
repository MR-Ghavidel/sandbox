{{-- Chevron button that collapses/expands the nearest [data-collapsible] container. --}}
<button type="button" data-collapse-toggle aria-expanded="true" {{ $attributes->merge(['class' => 'rounded-md p-1 text-slate-500 hover:bg-slate-100 hover:text-slate-700']) }} aria-label="جمع / باز کردن">
    <svg class="size-5 transition-transform group-data-[collapsed]/collapsible:-rotate-90" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>
</button>
