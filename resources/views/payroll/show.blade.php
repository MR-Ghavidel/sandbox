@use('App\Support\JalaliDate')

<x-layouts.app :title="'کارکرد و حقوق — '.$period->label()">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold">کارکرد {{ $period->label() }}</h1>
            <p class="mt-1 text-sm text-slate-500">
                از {{ JalaliDate::format($period->startDate, 'EEEE d MMMM') }} تا {{ JalaliDate::format($period->endDate, 'EEEE d MMMM y') }}
            </p>
        </div>

        <nav class="flex items-center overflow-hidden rounded-lg border border-slate-200 bg-white text-sm">
            <a href="{{ route('payroll.show', ['year' => $period->previous()->year, 'month' => $period->previous()->month]) }}" class="px-3 py-2 hover:bg-slate-50">&rarr; {{ $period->previous()->label() }}</a>
            <a href="{{ route('payroll.index') }}" class="border-x border-slate-200 px-3 py-2 font-medium hover:bg-slate-50">ماه جاری</a>
            <a href="{{ route('payroll.show', ['year' => $period->next()->year, 'month' => $period->next()->month]) }}" class="px-3 py-2 hover:bg-slate-50">{{ $period->next()->label() }} &larr;</a>
        </nav>
    </div>

    @if (session('status'))
        <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            <ul class="list-inside list-disc">
                @foreach (collect($errors->all())->unique() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <x-payroll.summary :summary="$summary" :settings="$settings" />

    <div class="grid grid-cols-1 items-start gap-4 xl:grid-cols-[1fr_22rem]">
        <x-payroll.days-table :days="$days" :period="$period" class="xl:order-1" />

        <div class="space-y-4 xl:order-2">
            <x-payroll.breakdown :summary="$summary" :settings="$settings" />
            <x-payroll.settings-form :settings="$settings" :period="$period" />
        </div>
    </div>
</x-layouts.app>
