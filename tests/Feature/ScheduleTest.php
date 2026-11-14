<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\SemesterSetting;
use App\Models\User;
use App\Services\ScheduleService;
use App\Support\JalaliDate;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScheduleTest extends TestCase
{
    use RefreshDatabase;

    public function test_odd_and_even_weeks_are_derived_from_the_manual_anchor(): void
    {
        $user = User::factory()->create();
        // 2026-11-14 is a Saturday: the first day of the Persian week.
        $setting = SemesterSetting::create([
            'user_id' => $user->id,
            'semester_start_date' => '2026-11-14',
            'first_week_is_odd' => true,
        ]);

        $schedule = app(ScheduleService::class);

        $firstWeek = $schedule->weekFor($setting, CarbonImmutable::parse('2026-11-18'));
        $secondWeek = $schedule->weekFor($setting, CarbonImmutable::parse('2026-11-21'));
        $thirdWeek = $schedule->weekFor($setting, CarbonImmutable::parse('2026-11-28'));

        $this->assertSame(1, $firstWeek['week_number']);
        $this->assertTrue($firstWeek['is_odd']);
        $this->assertSame(2, $secondWeek['week_number']);
        $this->assertFalse($secondWeek['is_odd']);
        $this->assertTrue($thirdWeek['is_odd']);
        $this->assertSame('2026-11-14', $firstWeek['week_start']->toDateString());
        $this->assertSame('2026-11-20', $firstWeek['week_end']->toDateString());
    }

    public function test_the_schedule_only_shows_classes_of_the_current_week_parity(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-11-18 10:00:00'));

        $user = User::factory()->create();
        SemesterSetting::create([
            'user_id' => $user->id,
            'semester_start_date' => '2026-11-14',
            'first_week_is_odd' => true,
        ]);

        $oddCourse = $this->courseFor($user, 'درس هفته فرد');
        $evenCourse = $this->courseFor($user, 'درس هفته زوج');
        $bothCourse = $this->courseFor($user, 'درس هر هفته');

        $oddCourse->sessions()->create(['day_of_week' => 3, 'starts_at' => '08:00', 'meets_odd_weeks' => true, 'meets_even_weeks' => false]);
        $evenCourse->sessions()->create(['day_of_week' => 3, 'starts_at' => '09:00', 'meets_odd_weeks' => false, 'meets_even_weeks' => true]);
        $bothCourse->sessions()->create(['day_of_week' => 3, 'starts_at' => '10:00', 'meets_odd_weeks' => true, 'meets_even_weeks' => true]);

        $this->actingAs($user)
            ->get(route('schedule.index'))
            ->assertOk()
            ->assertSee('درس هفته فرد')
            ->assertSee('درس هر هفته')
            ->assertDontSee('درس هفته زوج')
            ->assertSee('هفته فرد');

        $this->get(route('schedule.index', ['week' => JalaliDate::input(CarbonImmutable::parse('2026-11-21'))]))
            ->assertOk()
            ->assertSee('درس هفته زوج')
            ->assertSee('درس هر هفته')
            ->assertDontSee('درس هفته فرد')
            ->assertSee('هفته زوج');
    }

    public function test_changing_the_current_week_parity_flips_the_anchor_and_the_following_weeks(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-11-18 10:00:00'));

        $user = User::factory()->create();
        SemesterSetting::create([
            'user_id' => $user->id,
            'semester_start_date' => '2026-11-14',
            'first_week_is_odd' => true,
        ]);

        $this->actingAs($user)
            ->patch(route('settings.current-week'), ['current_week_is_odd' => '0'])
            ->assertRedirect();

        $this->assertDatabaseHas('semester_settings', ['user_id' => $user->id, 'first_week_is_odd' => false]);

        $schedule = app(ScheduleService::class);
        $setting = $schedule->setting($user);

        $this->assertFalse($schedule->weekFor($setting, CarbonImmutable::parse('2026-11-18'))['is_odd']);
        $this->assertTrue($schedule->weekFor($setting, CarbonImmutable::parse('2026-11-21'))['is_odd']);
    }

    public function test_the_semester_settings_are_saved_with_a_jalali_date(): void
    {
        $user = User::factory()->create();
        $start = JalaliDate::parts(CarbonImmutable::parse('2026-09-05'));

        $this->actingAs($user)
            ->put(route('settings.update'), [
                'semester_start_year' => $start['year'],
                'semester_start_month' => $start['month'],
                'semester_start_day' => $start['day'],
                'first_week_is_odd' => '0',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('semester_settings', [
            'user_id' => $user->id,
            'semester_start_date' => '2026-09-05 00:00:00',
            'first_week_is_odd' => false,
        ]);
    }

    public function test_an_invalid_jalali_date_is_rejected(): void
    {
        $this->actingAs(User::factory()->create())
            ->put(route('settings.update'), [
                'semester_start_year' => 1405,
                'semester_start_month' => 13,
                'semester_start_day' => 1,
                'first_week_is_odd' => '1',
            ])
            ->assertSessionHasErrors('semester_start_month');
    }

    public function test_the_settings_and_course_pages_render(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('settings.edit'))
            ->assertOk()
            ->assertSee('مبنای ترم')
            ->assertSee('ربات بله')
            ->assertSee('این هفته');

        $this->get(route('courses.index'))
            ->assertOk()
            ->assertSee('افزودن درس');
    }

    private function courseFor(User $user, string $name): Course
    {
        return Course::create(['user_id' => $user->id, 'name' => $name, 'color' => 'sky']);
    }
}
