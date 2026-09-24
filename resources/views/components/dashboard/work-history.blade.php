@props(['history'])

@use('App\Entities\AttendanceDayEntity')
@use('App\Support\Duration')
@use('App\Support\JalaliDate')
@use('Illuminate\Support\Number')

@php
    $fa = fn (int|float $number, int $maxPrecision = 0): string => Number::format($number, maxPrecision: $maxPrecision, locale: 'fa');
    $tenureParts = collect([
        [$history->tenure->y, 'سال'],
        [$history->tenure->m, 'ماه'],
        [$history->tenure->d, 'روز'],
    ])->filter(fn (array $part): bool => $part[0] > 0)->map(fn (array $part): string => $fa($part[0]).' '.$part[1]);
    $workedHours = intdiv($history->workedMinutes, 60);
@endphp

<section class="mb-6 overflow-hidden rounded-xl border border-violet-200 bg-gradient-to-bl from-violet-50 via-white to-sky-50 shadow-sm">
    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-violet-100 px-5 py-3">
        <h2 class="text-lg font-bold">کارنامه کاری من ✨</h2>
        <p class="text-xs text-slate-500">از {{ JalaliDate::format($history->firstDay, 'd MMMM y') }} تا امروز</p>
    </div>

    <div class="grid grid-cols-2 gap-px bg-violet-100 lg:grid-cols-4">
        <div class="bg-white/80 p-4">
            <p class="text-sm text-slate-500">مدت همکاری</p>
            <p class="mt-1 text-xl font-bold">{{ $tenureParts->implode(' و ') ?: 'از امروز' }}</p>
        </div>
        <div class="bg-white/80 p-4">
            <p class="text-sm text-slate-500">مجموع ساعت کار</p>
            <p class="mt-1 text-xl font-bold">{{ $fa($workedHours) }} <span class="text-base font-normal text-slate-500">ساعت</span></p>
            <p class="mt-1 text-xs text-slate-500">در {{ $fa($history->attendedDays) }} روز حضور</p>
        </div>
        <div class="bg-white/80 p-4">
            <p class="text-sm text-slate-500">مجموع دریافتی</p>
            <p class="mt-1 text-xl font-bold text-sky-800"><span data-sensitive>{{ $fa($history->receivedAmount) }}</span></p>
            <p class="mt-1 text-xs text-slate-500">
                @if ($history->paidMonths > 0)
                    {{ $fa($history->paidMonths) }} ماه تمام‌شده با حقوق ثبت‌شده
                @else
                    حقوق هیچ ماه تمام‌شده‌ای ثبت نشده
                @endif
            </p>
        </div>
        <div class="bg-white/80 p-4">
            <p class="text-sm text-slate-500">{{ $history->overtimeMinutes < 0 ? 'کسر کار خالص' : 'اضافه‌کار خالص' }}</p>
            <p @class(['mt-1 text-xl font-bold', 'text-emerald-700' => $history->overtimeMinutes > 0, 'text-red-700' => $history->overtimeMinutes < 0]) dir="ltr">{{ Duration::format($history->overtimeMinutes, withPlusSign: true) }}</p>
            <p class="mt-1 text-xs text-slate-500">در ماه‌های تمام‌شده</p>
        </div>
    </div>

    <ul class="grid gap-x-6 gap-y-2 px-5 py-4 text-sm text-slate-700 sm:grid-cols-2">
        @if ($history->averageArrivalMinutes !== null)
            <li>⏰ میانگین ساعت ورود: <strong dir="ltr">{{ Duration::format($history->averageArrivalMinutes) }}</strong></li>
        @endif
        @if ($history->earliestArrival)
            <li>🌅 زودترین ورود: <strong dir="ltr">{{ Duration::format(\App\Entities\AttendanceDayEntity::toMinutes($history->earliestArrival['time'])) }}</strong> <span class="text-slate-500">({{ JalaliDate::format($history->earliestArrival['date'], 'd MMMM y') }})</span></li>
        @endif
        @if ($history->latestLeave)
            <li>🌙 دیرترین خروج: <strong dir="ltr">{{ Duration::format(\App\Entities\AttendanceDayEntity::toMinutes($history->latestLeave['time'])) }}</strong> <span class="text-slate-500">({{ JalaliDate::format($history->latestLeave['date'], 'd MMMM y') }})</span></li>
        @endif
        @if ($history->longestDay)
            <li>🏃 طولانی‌ترین روز: <strong dir="ltr">{{ Duration::format($history->longestDay['minutes']) }}</strong> ساعت <span class="text-slate-500">({{ JalaliDate::format($history->longestDay['date'], 'EEEE d MMMM y') }})</span></li>
        @endif
        <li>🛌 اگر یک‌سره کار می‌کردی، می‌شد <strong>{{ $fa($history->workedFullDays(), 1) }}</strong> شبانه‌روز بی‌وقفه</li>
        @if ($history->lordOfTheRingsMarathons() > 0)
            <li>🎬 با این وقت می‌شد <strong>{{ $fa($history->lordOfTheRingsMarathons()) }}</strong> بار نسخه کامل سه‌گانه ارباب حلقه‌ها را دید</li>
        @endif
    </ul>
</section>
