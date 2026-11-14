<?php

namespace Tests\Feature;

use App\Models\Alarm;
use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_course_is_created_with_its_links_and_weekly_sessions(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('courses.store'), [
                'name' => 'مبانی برنامه‌نویسی',
                'instructor' => 'دکتر رضایی',
                'location' => 'کلاس ۲۰۴',
                'color' => 'emerald',
            ])
            ->assertRedirect();

        $course = Course::sole();
        $this->assertSame('مبانی برنامه‌نویسی', $course->name);

        $this->post(route('courses.links.store', $course->id), [
            'title' => 'گروه درس',
            'url' => 'https://bale.ai/group/test',
        ])->assertRedirect(route('courses.show', $course->id));

        $this->post(route('courses.sessions.store', $course->id), [
            'day_of_week' => 0,
            'starts_at' => '8:00',
            'ends_at' => '10:00',
            'meets_odd_weeks' => '1',
            'meets_even_weeks' => '0',
        ])->assertRedirect(route('courses.show', $course->id));

        $this->assertDatabaseHas('course_links', [
            'course_id' => $course->id,
            'title' => 'گروه درس',
            'url' => 'https://bale.ai/group/test',
        ]);
        $this->assertDatabaseHas('class_sessions', [
            'course_id' => $course->id,
            'day_of_week' => 0,
            'starts_at' => '08:00',
            'meets_odd_weeks' => true,
            'meets_even_weeks' => false,
        ]);

        $this->get(route('courses.show', $course->id))
            ->assertOk()
            ->assertSee('مبانی برنامه‌نویسی')
            ->assertSee('دکتر رضایی')
            ->assertSee('گروه درس')
            ->assertSee('فقط هفته‌های فرد')
            ->assertSee('شنبه');
    }

    public function test_links_and_sessions_can_be_edited_and_deleted_through_the_course(): void
    {
        $user = User::factory()->create();
        $course = Course::create(['user_id' => $user->id, 'name' => 'ریاضی', 'color' => 'sky']);
        $link = $course->links()->create(['title' => 'جزوه', 'url' => 'https://example.test/notes']);
        $session = $course->sessions()->create(['day_of_week' => 1, 'starts_at' => '10:00', 'meets_odd_weeks' => true, 'meets_even_weeks' => true]);

        $this->actingAs($user)
            ->put(route('courses.links.update', [$course->id, $link->id]), ['title' => 'جزوه جدید', 'url' => 'https://example.test/new'])
            ->assertRedirect(route('courses.show', $course->id));

        $this->assertDatabaseHas('course_links', ['id' => $link->id, 'title' => 'جزوه جدید']);

        $this->put(route('courses.sessions.update', [$course->id, $session->id]), [
            'day_of_week' => 2,
            'starts_at' => '12:00',
            'ends_at' => '13:30',
            'meets_odd_weeks' => '0',
            'meets_even_weeks' => '1',
        ])->assertRedirect(route('courses.show', $course->id));

        $this->assertDatabaseHas('class_sessions', [
            'id' => $session->id,
            'day_of_week' => 2,
            'starts_at' => '12:00',
            'meets_odd_weeks' => false,
            'meets_even_weeks' => true,
        ]);

        $this->delete(route('courses.links.destroy', [$course->id, $link->id]))->assertRedirect();
        $this->delete(route('courses.sessions.destroy', [$course->id, $session->id]))->assertRedirect();

        $this->assertDatabaseCount('course_links', 0);
        $this->assertDatabaseCount('class_sessions', 0);
    }

    public function test_a_class_session_needs_a_parity_and_a_valid_time_range(): void
    {
        $user = User::factory()->create();
        $course = Course::create(['user_id' => $user->id, 'name' => 'فیزیک', 'color' => 'sky']);

        $this->actingAs($user)
            ->post(route('courses.sessions.store', $course->id), [
                'day_of_week' => 0,
                'starts_at' => '10:00',
                'ends_at' => '09:00',
                'meets_odd_weeks' => '0',
                'meets_even_weeks' => '0',
            ])
            ->assertSessionHasErrors(['meets_odd_weeks', 'ends_at']);

        $this->assertDatabaseCount('class_sessions', 0);
    }

    public function test_deleting_a_course_also_removes_the_alarms_of_its_sessions(): void
    {
        $user = User::factory()->create();
        $course = Course::create(['user_id' => $user->id, 'name' => 'حذف‌شدنی', 'color' => 'sky']);
        $session = $course->sessions()->create(['day_of_week' => 0, 'starts_at' => '08:00', 'meets_odd_weeks' => true, 'meets_even_weeks' => true]);
        Alarm::create([
            'user_id' => $user->id,
            'alarmable_type' => $session->getMorphClass(),
            'alarmable_id' => $session->id,
            'minutes_before' => 30,
        ]);

        $this->actingAs($user)->delete(route('courses.destroy', $course->id))->assertRedirect(route('courses.index'));

        $this->assertDatabaseCount('courses', 0);
        $this->assertDatabaseCount('class_sessions', 0);
        $this->assertDatabaseCount('alarms', 0);
    }

    public function test_a_course_belongs_only_to_its_owner(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $course = Course::create(['user_id' => $owner->id, 'name' => 'شیمی', 'color' => 'sky']);

        $this->actingAs($other)->get(route('courses.show', $course->id))->assertNotFound();
        $this->actingAs($other)->get(route('courses.edit', $course->id))->assertNotFound();
        $this->actingAs($other)->post(route('courses.links.store', $course->id), ['title' => 'x', 'url' => 'https://x.test'])->assertNotFound();
        $this->actingAs($other)->post(route('courses.sessions.store', $course->id), [
            'day_of_week' => 0, 'starts_at' => '08:00', 'meets_odd_weeks' => '1', 'meets_even_weeks' => '1',
        ])->assertNotFound();
    }
}
