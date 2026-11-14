<?php

namespace App\Services;

use App\Models\ClassSession;
use App\Models\SemesterSetting;
use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Works out the odd/even week of a date and which class sessions happen in it.
 *
 * Odd/even status is always derived from the user's manual anchor, never detected.
 */
class ScheduleService
{
    private const SECONDS_PER_WEEK = 604800;

    public function setting(User $user): SemesterSetting
    {
        return $user->semesterSetting()->firstOrCreate([], [
            'semester_start_date' => CarbonImmutable::now()->startOfWeek(CarbonInterface::SATURDAY)->toDateString(),
            'first_week_is_odd' => true,
        ]);
    }

    /**
     * @return array{week_start: CarbonImmutable, week_end: CarbonImmutable, week_number: int, is_odd: bool}
     */
    public function week(User $user, CarbonImmutable $date, ?SemesterSetting $setting = null): array
    {
        return $this->weekFor($setting ?? $this->setting($user), $date);
    }

    /**
     * @return array{week_start: CarbonImmutable, week_end: CarbonImmutable, week_number: int, is_odd: bool}
     */
    public function weekFor(SemesterSetting $setting, CarbonImmutable $date): array
    {
        $weekStart = $date->startOfWeek(CarbonInterface::SATURDAY)->startOfDay();
        $anchorWeekStart = CarbonImmutable::parse($setting->semester_start_date)
            ->startOfWeek(CarbonInterface::SATURDAY)
            ->startOfDay();

        $weeksSinceStart = (int) round(($weekStart->getTimestamp() - $anchorWeekStart->getTimestamp()) / self::SECONDS_PER_WEEK);
        $isOdd = $weeksSinceStart % 2 === 0 ? $setting->first_week_is_odd : ! $setting->first_week_is_odd;

        return [
            'week_start' => $weekStart,
            'week_end' => $weekStart->addDays(6),
            'week_number' => $weeksSinceStart + 1,
            'is_odd' => $isOdd,
        ];
    }

    /**
     * All class occurrences of a week, each with the date of the occurrence.
     *
     * @return Collection<int, array{session: ClassSession, date: CarbonImmutable, starts_at: CarbonImmutable, ends_at: ?CarbonImmutable}>
     */
    public function occurrencesForWeek(User $user, CarbonImmutable $weekStart, ?SemesterSetting $setting = null): Collection
    {
        $setting ??= $this->setting($user);
        $week = $this->weekFor($setting, $weekStart);

        $sessions = ClassSession::query()
            ->with('course')
            ->ownedBy($user)
            ->forParity($week['is_odd'])
            ->orderBy('day_of_week')
            ->orderBy('starts_at')
            ->get();

        return $sessions->map(function (ClassSession $session) use ($week): array {
            $date = $week['week_start']->addDays($session->day_of_week);

            return [
                'session' => $session,
                'date' => $date,
                'starts_at' => CarbonImmutable::parse($date->toDateString().' '.$session->starts_at),
                'ends_at' => $session->ends_at === null
                    ? null
                    : CarbonImmutable::parse($date->toDateString().' '.$session->ends_at),
            ];
        })->values();
    }

    /**
     * @return Collection<int, array{session: ClassSession, date: CarbonImmutable, starts_at: CarbonImmutable, ends_at: ?CarbonImmutable}>
     */
    public function occurrencesForDate(User $user, CarbonImmutable $date): Collection
    {
        return $this->occurrencesForWeek($user, $date->startOfWeek(CarbonInterface::SATURDAY))
            ->filter(fn (array $occurrence): bool => $occurrence['date']->isSameDay($date))
            ->values();
    }

    /**
     * The start of a session on a given date, when the session actually happens that week.
     */
    public function occurrenceOn(ClassSession $session, CarbonImmutable $date, SemesterSetting $setting): ?CarbonImmutable
    {
        $week = $this->weekFor($setting, $date);

        if (! $session->meetsParity($week['is_odd'])) {
            return null;
        }

        $sessionDate = $week['week_start']->addDays($session->day_of_week);

        return $sessionDate->isSameDay($date)
            ? CarbonImmutable::parse($sessionDate->toDateString().' '.$session->starts_at)
            : null;
    }

    /**
     * The next time a session starts, searching the coming two weeks.
     */
    public function nextOccurrence(ClassSession $session, CarbonImmutable $from, SemesterSetting $setting): ?CarbonImmutable
    {
        for ($day = 0; $day <= 14; $day++) {
            $occurrence = $this->occurrenceOn($session, $from->startOfDay()->addDays($day), $setting);

            if ($occurrence !== null && $occurrence->greaterThanOrEqualTo($from)) {
                return $occurrence;
            }
        }

        return null;
    }

    /**
     * Flip the semester anchor when the user says the current week is the other parity.
     */
    public function setCurrentWeekParity(User $user, bool $isOdd): SemesterSetting
    {
        $setting = $this->setting($user);
        $current = $this->weekFor($setting, CarbonImmutable::now());

        if ($current['is_odd'] !== $isOdd) {
            $setting->update(['first_week_is_odd' => ! $setting->first_week_is_odd]);
            $setting->refresh();
        }

        return $setting;
    }
}
