<?php

namespace Tests\Feature;

use App\Models\Alarm;
use App\Models\AlarmDelivery;
use App\Models\Assignment;
use App\Models\BaleSubscriber;
use App\Models\Course;
use App\Models\SemesterSetting;
use App\Models\User;
use App\Support\JalaliDate;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AlarmDispatchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'bale.token' => 'test-token',
            'bale.base_url' => 'https://tapi.bale.ai',
        ]);

        Http::preventStrayRequests();
        Http::fake(['https://tapi.bale.ai/*' => Http::response(['ok' => true], 200)]);
    }

    public function test_a_class_reminder_is_sent_through_bale_only_once(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-11-18 10:00:00'));

        $user = $this->userWithBale('123456');
        SemesterSetting::create([
            'user_id' => $user->id,
            'semester_start_date' => '2026-11-14',
            'first_week_is_odd' => true,
        ]);
        $course = Course::create(['user_id' => $user->id, 'name' => 'مبانی برنامه‌نویسی', 'location' => 'کلاس ۲۰۴', 'color' => 'sky']);
        $session = $course->sessions()->create([
            'day_of_week' => JalaliDate::weekdayIndex(now()),
            'starts_at' => '10:30',
            'meets_odd_weeks' => true,
            'meets_even_weeks' => true,
        ]);
        $alarm = Alarm::create([
            'user_id' => $user->id,
            'alarmable_type' => $session->getMorphClass(),
            'alarmable_id' => $session->id,
            'minutes_before' => 30,
        ]);

        $this->artisan('alarms:dispatch')->assertSuccessful();

        $this->assertDatabaseCount('alarm_deliveries', 1);
        $this->assertNotNull(AlarmDelivery::sole()->sent_at);

        Http::assertSent(function (Request $request): bool {
            $data = $request->data();

            return str_contains($request->url(), 'sendMessage')
                && ($data['chat_id'] ?? null) === '123456'
                && str_contains((string) ($data['text'] ?? ''), 'مبانی برنامه‌نویسی')
                && str_contains((string) ($data['text'] ?? ''), 'کلاس ۲۰۴');
        });

        // Running the scheduler again does not repeat the same reminder.
        $this->artisan('alarms:dispatch')->assertSuccessful();
        Http::assertSentCount(1);
    }

    public function test_an_assignment_reminder_is_sent_when_its_interval_is_due(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-11-18 10:00:00'));

        $user = $this->userWithBale('654321');
        $assignment = Assignment::create([
            'user_id' => $user->id,
            'title' => 'تحقیق آمار',
            'due_at' => now()->addMinutes(30),
            'is_done' => false,
        ]);
        Alarm::create([
            'user_id' => $user->id,
            'alarmable_type' => $assignment->getMorphClass(),
            'alarmable_id' => $assignment->id,
            'minutes_before' => 60,
        ]);

        $this->artisan('alarms:dispatch')->assertSuccessful();

        $this->assertDatabaseCount('alarm_deliveries', 1);
        Http::assertSent(function (Request $request): bool {
            $data = $request->data();

            return str_contains((string) ($data['text'] ?? ''), 'تحقیق آمار')
                && str_contains((string) ($data['text'] ?? ''), 'یادآور تحویل کار');
        });
    }

    public function test_nothing_is_sent_when_the_bot_is_not_connected(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-11-18 10:00:00'));

        $user = User::factory()->create();
        $assignment = Assignment::create([
            'user_id' => $user->id,
            'title' => 'بدون ربات',
            'due_at' => now()->addMinutes(30),
            'is_done' => false,
        ]);
        Alarm::create([
            'user_id' => $user->id,
            'alarmable_type' => $assignment->getMorphClass(),
            'alarmable_id' => $assignment->id,
            'minutes_before' => 60,
        ]);

        $this->artisan('alarms:dispatch')->assertSuccessful();

        $this->assertDatabaseCount('alarm_deliveries', 0);
        Http::assertNothingSent();
    }

    private function userWithBale(string $chatId): User
    {
        $user = User::factory()->create();
        BaleSubscriber::create([
            'user_id' => $user->id,
            'chat_id' => $chatId,
            'linked_at' => now(),
        ]);

        return $user;
    }
}
