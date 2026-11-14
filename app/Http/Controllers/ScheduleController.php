<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Services\ScheduleService;
use App\Support\JalaliDate;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ScheduleController extends Controller
{
    /**
     * Show the weekly timetable for the week the user picked (or the current week).
     */
    public function index(Request $request, ScheduleService $schedule): View
    {
        $user = $request->user();
        $setting = $schedule->setting($user);
        $date = JalaliDate::parse((string) $request->query('week')) ?? CarbonImmutable::now();
        $week = $schedule->weekFor($setting, $date);
        $occurrences = $schedule->occurrencesForWeek($user, $week['week_start'], $setting);

        $assignments = Assignment::query()
            ->with('course')
            ->ownedBy($user)
            ->pending()
            ->whereBetween('due_at', [$week['week_start'], $week['week_end']->endOfDay()])
            ->orderBy('due_at')
            ->get();

        return view('schedule.index', [
            'week' => $week,
            'setting' => $setting,
            'occurrencesByDay' => $occurrences->groupBy(fn (array $occurrence): int => $occurrence['session']->day_of_week),
            'occurrences' => $occurrences,
            'assignments' => $assignments,
            'previousWeek' => $week['week_start']->subWeek(),
            'nextWeek' => $week['week_start']->addWeek(),
        ]);
    }
}
