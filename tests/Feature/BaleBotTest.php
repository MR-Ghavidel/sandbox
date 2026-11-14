<?php

namespace Tests\Feature;

use App\Models\Alarm;
use App\Models\Assignment;
use App\Models\BaleLinkCode;
use App\Models\BaleSubscriber;
use App\Models\Course;
use App\Models\SemesterSetting;
use App\Models\User;
use App\Support\JalaliDate;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class BaleBotTest extends TestCase
{
    use RefreshDatabase;

    private const CHAT_ID = 987654;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'bale.token' => 'test-token',
            'bale.base_url' => 'https://tapi.bale.ai',
            'bale.webhook_secret' => null,
        ]);

        Http::preventStrayRequests();
        Http::fake(['https://tapi.bale.ai/*' => Http::response(['ok' => true], 200)]);
    }

    public function test_a_link_code_connects_the_bale_chat_to_the_account(): void
    {
        $user = User::factory()->create(['name' => 'کاربر تست']);
        $code = BaleLinkCode::create([
            'user_id' => $user->id,
            'code' => 'ABCD1234',
            'expires_at' => now()->addMinutes(30),
        ]);

        $this->sendMessage('/start '.$code->code)->assertOk();

        $this->assertDatabaseHas('bale_subscribers', [
            'user_id' => $user->id,
            'chat_id' => (string) self::CHAT_ID,
            'first_name' => 'تست',
        ]);
        $this->assertDatabaseCount('bale_link_codes', 0);

        Http::assertSent(fn (Request $request): bool => str_contains((string) ($request->data()['text'] ?? ''), 'حساب شما با موفقیت'));
    }

    public function test_the_bot_shows_the_weekly_schedule_and_course_links(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-11-18 10:00:00'));

        $user = $this->linkedUser();
        SemesterSetting::create([
            'user_id' => $user->id,
            'semester_start_date' => '2026-11-14',
            'first_week_is_odd' => true,
        ]);
        $course = Course::create([
            'user_id' => $user->id,
            'name' => 'ریاضی ۲',
            'instructor' => 'دکتر احمدی',
            'location' => 'کلاس ۱۲',
            'color' => 'sky',
        ]);
        $course->links()->create(['title' => 'گروه درس', 'url' => 'https://bale.ai/group/math']);
        $course->sessions()->create([
            'day_of_week' => JalaliDate::weekdayIndex(now()),
            'starts_at' => '10:00',
            'meets_odd_weeks' => true,
            'meets_even_weeks' => false,
        ]);

        $this->sendMessage('برنامه هفته')->assertOk();

        Http::assertSent(fn (Request $request): bool => str_contains((string) ($request->data()['text'] ?? ''), 'ریاضی ۲')
            && str_contains((string) ($request->data()['text'] ?? ''), 'هفته فرد'));

        $this->sendCallback('course:'.$course->id)->assertOk();

        Http::assertSent(fn (Request $request): bool => str_contains((string) ($request->data()['text'] ?? ''), 'گروه درس')
            && str_contains((string) ($request->data()['text'] ?? ''), 'https://bale.ai/group/math')
            && str_contains((string) ($request->data()['text'] ?? ''), 'دکتر احمدی'));

        Http::assertSent(fn (Request $request): bool => str_contains($request->url(), 'editMessageText')
            && ($request->data()['chat_id'] ?? null) === (string) self::CHAT_ID
            && ($request->data()['message_id'] ?? null) === 123456);
    }

    public function test_the_main_menu_uses_inline_buttons_and_callback_click_edits_its_message(): void
    {
        $user = $this->linkedUser();
        Course::create(['user_id' => $user->id, 'name' => 'ریاضی', 'color' => 'sky']);

        $this->sendMessage('/start')->assertOk();

        Http::assertSent(fn (Request $request): bool => str_contains($request->url(), 'sendMessage')
            && ($request->data()['reply_markup']['remove_keyboard'] ?? false) === true);
        Http::assertSent(fn (Request $request): bool => str_contains($request->url(), 'sendMessage')
            && str_contains(json_encode($request->data()['reply_markup'] ?? []), 'menu:help'));

        $this->sendCallback('menu:courses')->assertOk();

        Http::assertSent(fn (Request $request): bool => str_contains($request->url(), 'editMessageText')
            && ($request->data()['message_id'] ?? null) === 123456
            && str_contains((string) ($request->data()['text'] ?? ''), 'درس‌های شما'));
    }

    public function test_alarms_are_created_and_deleted_from_the_bot(): void
    {
        $user = $this->linkedUser();
        $course = Course::create(['user_id' => $user->id, 'name' => 'شبکه', 'color' => 'teal']);
        $session = $course->sessions()->create([
            'day_of_week' => 0,
            'starts_at' => '08:00',
            'meets_odd_weeks' => true,
            'meets_even_weeks' => true,
        ]);

        $this->sendCallback('alarm_set:session:'.$session->id.':30')->assertOk();

        $this->assertDatabaseHas('alarms', [
            'user_id' => $user->id,
            'alarmable_id' => $session->id,
            'minutes_before' => 30,
        ]);

        $alarm = Alarm::sole();

        $this->sendCallback('alarm_del:'.$alarm->id.':session:'.$session->id)->assertOk();
        $this->assertDatabaseCount('alarms', 0);
    }

    public function test_an_assignment_is_created_step_by_step_in_the_bot(): void
    {
        $user = $this->linkedUser();
        $course = Course::create(['user_id' => $user->id, 'name' => 'آمار', 'color' => 'amber']);

        $this->sendCallback('assignment_new')->assertOk();
        $this->sendCallback('assignment_course:'.$course->id)->assertOk();
        $this->sendMessage('تحقیق پایانی')->assertOk();
        $this->sendMessage('1405/09/01')->assertOk();
        $this->sendMessage('14:30')->assertOk();

        $assignment = Assignment::sole();

        $this->assertSame('تحقیق پایانی', $assignment->title);
        $this->assertSame($course->id, $assignment->course_id);
        $this->assertSame('2026-11-22 14:30:00', $assignment->due_at->format('Y-m-d H:i:s'));
        $this->assertNull(BaleSubscriber::sole()->state);
    }

    public function test_the_webhook_checks_its_secret_when_one_is_configured(): void
    {
        config(['bale.webhook_secret' => 'top-secret']);

        $this->postJson('/bale/webhook/wrong-secret', $this->messageUpdate('سلام'))->assertNotFound();
    }

    private function linkedUser(): User
    {
        $user = User::factory()->create();
        BaleSubscriber::create([
            'user_id' => $user->id,
            'chat_id' => (string) self::CHAT_ID,
            'first_name' => 'تست',
            'linked_at' => now(),
        ]);

        return $user;
    }

    private function sendMessage(string $text): TestResponse
    {
        return $this->postJson(route('bale.webhook'), $this->messageUpdate($text));
    }

    private function sendCallback(string $data): TestResponse
    {
        return $this->postJson(route('bale.webhook'), [
            'update_id' => random_int(1, 100000),
            'callback_query' => [
                'id' => 'callback-'.random_int(1, 100000),
                'data' => $data,
                'message' => [
                    'message_id' => 123456,
                    'chat' => ['id' => self::CHAT_ID],
                ],
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function messageUpdate(string $text): array
    {
        return [
            'update_id' => random_int(1, 100000),
            'message' => [
                'message_id' => random_int(1, 100000),
                'chat' => ['id' => self::CHAT_ID],
                'from' => ['first_name' => 'تست', 'username' => 'tester'],
                'text' => $text,
            ],
        ];
    }
}
