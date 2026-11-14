<?php

namespace Tests\Feature;

use App\Models\Alarm;
use App\Models\Assignment;
use App\Models\Course;
use App\Models\User;
use App\Support\JalaliDate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_assignment_is_created_from_a_jalali_date_and_can_be_finished(): void
    {
        $user = User::factory()->create();
        $course = Course::create(['user_id' => $user->id, 'name' => 'آمار', 'color' => 'amber']);
        $dueDate = JalaliDate::toGregorian(1405, 9, 1);
        $parts = JalaliDate::parts($dueDate);

        $this->actingAs($user)
            ->post(route('assignments.store'), [
                'title' => 'پروژه پایانی',
                'course_id' => $course->id,
                'description' => 'فصل ۱ تا ۴',
                'due_date_year' => $parts['year'],
                'due_date_month' => $parts['month'],
                'due_date_day' => $parts['day'],
                'due_time' => '14:30',
            ])
            ->assertRedirect(route('assignments.index'));

        $assignment = Assignment::sole();
        $this->assertSame('2026-11-22 14:30:00', $assignment->due_at->format('Y-m-d H:i:s'));
        $this->assertSame($course->id, $assignment->course_id);

        $this->patch(route('assignments.done', $assignment->id))->assertRedirect();
        $this->assertDatabaseHas('assignments', ['id' => $assignment->id, 'is_done' => true]);

        $this->get(route('assignments.index'))
            ->assertOk()
            ->assertSee('پروژه پایانی')
            ->assertSee('آمار');
    }

    public function test_an_invalid_jalali_due_date_is_rejected(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('assignments.store'), [
                'title' => 'کار',
                'due_date_year' => 1405,
                'due_date_month' => 12,
                'due_date_day' => 31,
                'due_time' => '12:00',
            ])
            ->assertSessionHasErrors('due_date_day');
    }

    public function test_alarms_can_be_added_edited_and_deleted_for_an_assignment(): void
    {
        $user = User::factory()->create();
        $assignment = Assignment::create([
            'user_id' => $user->id,
            'title' => 'تحقیق',
            'due_at' => now()->addDays(3),
            'is_done' => false,
        ]);

        $this->actingAs($user)
            ->post(route('alarms.store'), ['target' => 'assignment:'.$assignment->id, 'minutes_before' => 1440])
            ->assertRedirect();

        $this->assertDatabaseHas('alarms', [
            'alarmable_type' => Assignment::class,
            'alarmable_id' => $assignment->id,
            'minutes_before' => 1440,
        ]);

        // The same interval is not stored twice.
        $this->post(route('alarms.store'), ['target' => 'assignment:'.$assignment->id, 'minutes_before' => 1440]);
        $this->assertDatabaseCount('alarms', 1);

        $alarmId = (int) Alarm::sole()->id;

        $this->patch(route('alarms.update', $alarmId), ['minutes_before' => 60])->assertRedirect();
        $this->assertDatabaseHas('alarms', ['id' => $alarmId, 'minutes_before' => 60]);

        $this->delete(route('alarms.destroy', $alarmId))->assertRedirect();
        $this->assertDatabaseCount('alarms', 0);
    }

    public function test_deleting_an_assignment_also_removes_its_alarms(): void
    {
        $user = User::factory()->create();
        $assignment = Assignment::create([
            'user_id' => $user->id,
            'title' => 'حذف‌شدنی',
            'due_at' => now()->addDay(),
            'is_done' => false,
        ]);
        Alarm::create([
            'user_id' => $user->id,
            'alarmable_type' => $assignment->getMorphClass(),
            'alarmable_id' => $assignment->id,
            'minutes_before' => 60,
        ]);

        $this->actingAs($user)->delete(route('assignments.destroy', $assignment->id))->assertRedirect(route('assignments.index'));

        $this->assertDatabaseCount('assignments', 0);
        $this->assertDatabaseCount('alarms', 0);
    }

    public function test_a_course_of_another_user_cannot_be_attached(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $course = Course::create(['user_id' => $owner->id, 'name' => 'شبکه', 'color' => 'sky']);

        $this->actingAs($other)
            ->post(route('assignments.store'), [
                'title' => 'کار',
                'course_id' => $course->id,
                'due_date_year' => 1405,
                'due_date_month' => 9,
                'due_date_day' => 1,
                'due_time' => '12:00',
            ])
            ->assertSessionHasErrors('course_id');
    }

    public function test_another_user_cannot_change_an_assignment_or_its_alarms(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $assignment = Assignment::create([
            'user_id' => $owner->id,
            'title' => 'خصوصی',
            'due_at' => now()->addDay(),
            'is_done' => false,
        ]);

        $this->actingAs($other)->patch(route('assignments.done', $assignment->id))->assertNotFound();
        $this->actingAs($other)->post(route('alarms.store'), ['target' => 'assignment:'.$assignment->id, 'minutes_before' => 30])->assertNotFound();
    }
}
