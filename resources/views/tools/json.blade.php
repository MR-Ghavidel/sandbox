<x-layouts.app title="مرتب‌سازی JSON">
    <div data-json-formatter>
        <div class="mb-4 flex flex-wrap items-end justify-between gap-4">
            <div>
                <a href="{{ route('tools.index') }}" class="text-sm text-sky-700 hover:underline">&rarr; ابزارها</a>
                <h1 class="mt-1 text-2xl font-bold">مرتب‌سازی JSON</h1>
                <p class="mt-1 text-sm text-slate-500">
                    متن را بچسبانید؛ خودکار مرتب می‌شود. کاراکترهای <code dir="ltr" class="rounded bg-slate-100 px-1">س</code> و حروف خراب مثل <code dir="ltr" class="rounded bg-slate-100 px-1">Ø³Ù„Ø§Ù…</code> درست نمایش داده می‌شوند.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-x-4 gap-y-2 text-sm">
                <label class="flex items-center gap-1.5">
                    فاصله
                    <select data-option="indent" class="rounded-md border border-slate-200 bg-white px-2 py-1">
                        <option value="2">۲ فاصله</option>
                        <option value="4">۴ فاصله</option>
                        <option value="tab">Tab</option>
                    </select>
                </label>
                <label class="flex items-center gap-1.5"><input type="checkbox" data-option="fixMojibake" checked class="size-4 accent-sky-600"> اصلاح حروف خراب</label>
                <label class="flex items-center gap-1.5"><input type="checkbox" data-option="parseNested" checked class="size-4 accent-sky-600"> باز کردن JSON داخل رشته‌ها</label>
                <label class="flex items-center gap-1.5"><input type="checkbox" data-option="sortKeys" class="size-4 accent-sky-600"> مرتب‌سازی کلیدها</label>
            </div>
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
            <section class="flex flex-col rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="flex items-center justify-between gap-2 border-b border-slate-100 px-4 py-2.5">
                    <h2 class="font-bold">ورودی</h2>
                    <div class="flex gap-1 text-sm">
                        <button type="button" data-action="sample" class="rounded-md px-2 py-1 text-slate-600 hover:bg-slate-100">نمونه</button>
                        <button type="button" data-action="clear" class="rounded-md px-2 py-1 text-slate-600 hover:bg-slate-100">پاک کردن</button>
                    </div>
                </div>
                <textarea data-json-input dir="ltr" spellcheck="false" placeholder='{"name": "سلام"}' class="min-h-[28rem] flex-1 resize-y rounded-b-xl p-4 font-mono text-sm leading-6 focus:outline-none"></textarea>
            </section>

            <section class="flex min-w-0 flex-col rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="flex items-center justify-between gap-2 border-b border-slate-100 px-4 py-2.5">
                    <div class="flex items-center gap-2">
                        <h2 class="font-bold">خروجی</h2>
                        <span data-json-status class="rounded-full px-2 py-0.5 text-xs"></span>
                    </div>
                    <div class="flex gap-1 text-sm">
                        <button type="button" data-action="minify" class="rounded-md px-2 py-1 text-slate-600 hover:bg-slate-100">فشرده</button>
                        <button type="button" data-action="format" class="rounded-md px-2 py-1 text-slate-600 hover:bg-slate-100">مرتب</button>
                        <button type="button" data-copy-from="[data-json-output]" class="rounded-md bg-sky-600 px-3 py-1 font-medium text-white hover:bg-sky-700">کپی</button>
                    </div>
                </div>
                <p data-json-notes class="hidden border-b border-slate-100 bg-sky-50 px-4 py-2 text-xs text-sky-800"></p>
                <pre data-json-output dir="ltr" class="min-h-[28rem] flex-1 overflow-auto p-4 text-left font-mono text-sm leading-6 whitespace-pre text-slate-800"></pre>
            </section>
        </div>
    </div>
</x-layouts.app>
