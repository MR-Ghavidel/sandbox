<?php

namespace App\Services;

use App\Models\Alarm;
use App\Models\AlarmDelivery;
use App\Models\Assignment;
use App\Models\ClassSession;
use App\Services\Bale\BaleClient;
use App\Support\JalaliDate;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Log;

/**
 * Finds reminders that became due and sends them through the Bale bot.
 *
 * Every reminder is recorded in "alarm_deliveries", so a reminder is never sent twice
 * (even when the scheduler runs every minute or the app restarts).
 */
class AlarmDispatcher
{
    public function __construct(private BaleClient $bale, private ScheduleService $schedule) {}

    /**
     * @return int Number of messages sent.
     */
    public function dispatchDue(?CarbonImmutable $now = null): int
    {
        $now ??= CarbonImmutable::now();
        $sent = 0;

        Alarm::query()
            ->with(['alarmable', 'user.baleSubscriber'])
            ->active()
            ->chunkById(200, function ($alarms) use ($now, &$sent): void {
                foreach ($alarms as $alarm) {
                    if ($this->dispatchAlarm($alarm, $now)) {
                        $sent++;
                    }
                }
            });

        return $sent;
    }

    private function dispatchAlarm(Alarm $alarm, CarbonImmutable $now): bool
    {
        $occurrence = $this->dueOccurrence($alarm, $now);

        if ($occurrence === null) {
            return false;
        }

        $chatId = $alarm->user?->baleSubscriber?->chat_id;

        if ($chatId === null) {
            return false;
        }

        $delivery = AlarmDelivery::firstOrCreate(
            ['alarm_id' => $alarm->id, 'occurs_at' => $occurrence],
            ['sent_at' => null],
        );

        if (! $delivery->wasRecentlyCreated) {
            return false;
        }

        if ($this->bale->sendMessage($chatId, $this->messageFor($alarm)) === null) {
            // Allow a later run to retry when the network or the bot token was unavailable.
            $delivery->delete();

            return false;
        }

        $delivery->update(['sent_at' => $now]);

        return true;
    }

    /**
     * The moment the reminder became due for this alarm, or null when it is not due.
     */
    private function dueOccurrence(Alarm $alarm, CarbonImmutable $now): ?CarbonImmutable
    {
        $alarmable = $alarm->alarmable;

        if ($alarmable instanceof ClassSession) {
            $occurrence = $this->schedule->nextOccurrence($alarmable, $now->subMinutes(2), $this->schedule->setting($alarm->user));
            $sendAt = $occurrence?->subMinutes($alarm->minutes_before);

            return $sendAt !== null && $sendAt->lessThanOrEqualTo($now) && $occurrence->greaterThanOrEqualTo($now)
                ? $sendAt
                : null;
        }

        if ($alarmable instanceof Assignment) {
            if ($alarmable->is_done) {
                return null;
            }

            $dueAt = CarbonImmutable::parse($alarmable->due_at);
            $sendAt = $dueAt->subMinutes($alarm->minutes_before);

            return $sendAt->lessThanOrEqualTo($now) && $dueAt->greaterThanOrEqualTo($now) ? $sendAt : null;
        }

        Log::warning('An alarm points at an unsupported target.', ['alarm_id' => $alarm->id]);

        return null;
    }

    private function messageFor(Alarm $alarm): string
    {
        $alarmable = $alarm->alarmable;

        if ($alarmable instanceof ClassSession) {
            $course = $alarmable->course;
            $setting = $this->schedule->setting($alarm->user);
            $now = CarbonImmutable::now();
            $occurrence = $this->schedule->nextOccurrence($alarmable, $now->subMinutes(2), $setting);

            $lines = [
                '⏰ <b>یادآور کلاس</b>',
                '',
                '<b>'.e($course->name).'</b>',
            ];

            if ($occurrence !== null) {
                $lines[] = '📅 '.JalaliDate::long($occurrence).'، ساعت '.JalaliDate::time($occurrence);
            }

            if (filled($course->location)) {
                $lines[] = '📍 '.e($course->location);
            }

            if (filled($course->instructor)) {
                $lines[] = '👤 '.e($course->instructor);
            }

            $lines[] = '';
            $lines[] = '⏱ '.$alarm->label();

            return implode("\n", $lines);
        }

        /** @var Assignment $alarmable */
        $lines = [
            '📝 <b>یادآور تحویل کار</b>',
            '',
            '<b>'.e($alarmable->title).'</b>',
        ];

        if ($alarmable->course !== null) {
            $lines[] = '📚 '.e($alarmable->course->name);
        }

        $lines[] = '📅 '.$alarmable->dueLabel();
        $lines[] = '';
        $lines[] = '⏱ '.$alarm->label();

        return implode("\n", $lines);
    }
}
