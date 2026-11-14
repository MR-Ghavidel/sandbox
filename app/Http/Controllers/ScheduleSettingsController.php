<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateScheduleSettingsRequest;
use App\Models\BaleLinkCode;
use App\Services\ScheduleService;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ScheduleSettingsController extends Controller
{
    public function edit(Request $request, ScheduleService $schedule): View
    {
        $user = $request->user();
        $setting = $schedule->setting($user);
        $currentWeek = $schedule->weekFor($setting, CarbonImmutable::now());

        $previewWeeks = collect(range(-2, 6))->map(fn (int $offset): array => $schedule->weekFor(
            $setting,
            CarbonImmutable::now()->addWeeks($offset),
        ));

        return view('settings.edit', [
            'setting' => $setting,
            'currentWeek' => $currentWeek,
            'previewWeeks' => $previewWeeks,
            'subscriber' => $user->baleSubscriber,
            'linkCode' => BaleLinkCode::query()
                ->where('user_id', $user->id)
                ->where('expires_at', '>', now())
                ->latest()
                ->first(),
            'botUsername' => config('bale.bot_username'),
            'botConfigured' => filled(config('bale.token')),
        ]);
    }

    public function update(UpdateScheduleSettingsRequest $request, ScheduleService $schedule): RedirectResponse
    {
        $schedule->setting($request->user())->update([
            'semester_start_date' => $request->semesterStart()->toDateString(),
            'first_week_is_odd' => $request->boolean('first_week_is_odd'),
        ]);

        return back()->with('status', 'تنظیمات ترم ذخیره شد.');
    }

    /**
     * "This week is odd/even": flip the anchor only when the current parity differs.
     */
    public function updateCurrentWeek(Request $request, ScheduleService $schedule): RedirectResponse
    {
        $validated = $request->validate([
            'current_week_is_odd' => ['required', 'boolean'],
        ]);

        $schedule->setCurrentWeekParity($request->user(), (bool) $validated['current_week_is_odd']);

        return back()->with('status', 'وضعیت هفته جاری ذخیره شد.');
    }
}
