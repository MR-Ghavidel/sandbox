<?php

namespace Tests\Feature;

use App\Entities\TaskEntity;
use App\Repositories\TaskRepository;
use App\TaskStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TaskManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Wednesday 1405/07/01 — its week runs from Saturday 2026-09-19 to Friday 2026-09-25.
        Carbon::setTestNow('2026-09-23 10:00:00');
    }

    public function test_board_shows_only_tasks_of_the_saturday_to_friday_week_in_order(): void
    {
        $this->createTask(['title' => 'Friday task', 'due_date' => '2026-09-25']);
        $this->createTask(['title' => 'Second on saturday', 'due_date' => '2026-09-19', 'position' => 2]);
        $this->createTask(['title' => 'First on saturday', 'due_date' => '2026-09-19', 'position' => 1]);
        $this->createTask(['title' => 'Previous friday', 'due_date' => '2026-09-18']);
        $this->createTask(['title' => 'Next saturday', 'due_date' => '2026-09-26']);

        $this->get(route('tasks.index'))
            ->assertOk()
            ->assertSeeInOrder(['شنبه', 'First on saturday', 'Second on saturday', 'جمعه', 'Friday task'])
            ->assertSee('۲۸ شهریور')
            ->assertDontSee('Previous friday')
            ->assertDontSee('Next saturday');
    }

    public function test_page_uses_the_main_layout_with_the_drawer_menu(): void
    {
        $this->get(route('tasks.index'))
            ->assertOk()
            ->assertSee('dir="rtl"', false)
            ->assertSee('data-drawer-toggle', false)
            ->assertSee('href="'.route('tasks.index').'"', false)
            ->assertSee('aria-current="page"', false);
    }

    public function test_today_summary_shows_todays_tasks_even_when_viewing_another_week(): void
    {
        $this->createTask(['title' => 'Due today', 'due_date' => '2026-09-23', 'status' => 'completed']);
        $this->createTask(['title' => 'Also today', 'due_date' => '2026-09-23']);

        $this->get(route('tasks.index', ['week' => '2026-10-10']))
            ->assertOk()
            ->assertSeeInOrder(['کارهای امروز', 'Due today', 'Also today', 'روزهای هفته'])
            ->assertSee('style="width: 50%"', false);
    }

    public function test_board_can_show_another_week(): void
    {
        $this->createTask(['title' => 'Next saturday', 'due_date' => '2026-09-26']);

        $this->get(route('tasks.index', ['week' => '2026-09-29']))
            ->assertOk()
            ->assertSee('Next saturday');
    }

    public function test_task_is_added_to_the_end_of_its_day(): void
    {
        $this->createTask(['due_date' => '2026-09-21', 'position' => 3]);

        $this->post(route('tasks.store'), ['title' => 'New task', 'due_date' => '2026-09-21'])
            ->assertRedirect();

        $task = TaskEntity::fromRow(DB::table('tasks')->where('title', 'New task')->sole());
        $this->assertSame(4, $task->position);
        $this->assertSame(TaskStatus::NotStarted, $task->status);
    }

    public function test_task_can_be_created_with_a_description(): void
    {
        $this->post(route('tasks.store'), ['title' => 'With notes', 'description' => "Line one\nLine two", 'due_date' => '2026-09-21'])
            ->assertRedirect();

        $task = TaskEntity::fromRow(DB::table('tasks')->where('title', 'With notes')->sole());
        $this->assertSame("Line one\nLine two", $task->description);
    }

    public function test_board_includes_task_details_for_the_details_dialog(): void
    {
        $this->createTask(['title' => 'Detailed', 'description' => 'Full details here', 'status' => 'in_progress', 'due_date' => '2026-09-19']);

        $this->get(route('tasks.index'))
            ->assertOk()
            ->assertSee('id="show-task-dialog"', false)
            ->assertSee('"description":"Full details here"', false)
            ->assertSee('"status_label":'.json_encode('در حال انجام'), false)
            ->assertSee('"due_date_label":'.json_encode('شنبه ۲۸ شهریور ۱۴۰۵'), false);
    }

    public function test_task_requires_a_title_and_valid_date(): void
    {
        $this->post(route('tasks.store'), ['title' => '', 'due_date' => 'tomorrow'])
            ->assertSessionHasErrors(['title', 'due_date']);

        $this->assertDatabaseCount('tasks', 0);
    }

    public function test_status_can_be_changed_via_json(): void
    {
        $taskId = $this->createTask();

        $this->patchJson(route('tasks.update', $taskId), ['status' => 'completed'])
            ->assertOk()
            ->assertJson(['status' => 'completed', 'status_label' => 'تکمیل شده']);

        $this->assertSame(TaskStatus::Completed, $this->findTask($taskId)->status);
    }

    public function test_invalid_status_is_rejected(): void
    {
        $taskId = $this->createTask();

        $this->patchJson(route('tasks.update', $taskId), ['status' => 'archived'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('status');
    }

    public function test_task_title_and_description_can_be_edited(): void
    {
        $taskId = $this->createTask();

        $this->patch(route('tasks.update', $taskId), ['title' => 'Renamed', 'description' => 'Notes', 'status' => 'in_progress'])
            ->assertRedirect();

        $task = $this->findTask($taskId);
        $this->assertSame('Renamed', $task->title);
        $this->assertSame('Notes', $task->description);
        $this->assertSame(TaskStatus::InProgress, $task->status);
    }

    public function test_updating_or_deleting_a_missing_task_returns_not_found(): void
    {
        $this->patchJson(route('tasks.update', 999), ['status' => 'completed'])->assertNotFound();
        $this->delete(route('tasks.destroy', 999))->assertNotFound();
    }

    public function test_task_can_be_deleted(): void
    {
        $taskId = $this->createTask();

        $this->delete(route('tasks.destroy', $taskId))->assertRedirect();

        $this->assertDatabaseMissing('tasks', ['id' => $taskId]);
    }

    public function test_tasks_can_be_reordered_and_moved_between_days(): void
    {
        $first = $this->createTask(['due_date' => '2026-09-19', 'position' => 1]);
        $second = $this->createTask(['due_date' => '2026-09-19', 'position' => 2]);
        $third = $this->createTask(['due_date' => '2026-09-19', 'position' => 3]);
        $monday = $this->createTask(['due_date' => '2026-09-21', 'position' => 1]);

        $this->patchJson(route('tasks.order'), [
            'days' => [
                ['date' => '2026-09-19', 'task_ids' => [$third, $first]],
                ['date' => '2026-09-21', 'task_ids' => [$monday, $second]],
            ],
        ])->assertNoContent();

        $this->assertTaskPlacement($third, '2026-09-19', 1);
        $this->assertTaskPlacement($first, '2026-09-19', 2);
        $this->assertTaskPlacement($second, '2026-09-21', 2);
    }

    public function test_reorder_accepts_an_emptied_day_and_rejects_unknown_tasks(): void
    {
        $taskId = $this->createTask(['due_date' => '2026-09-19']);

        $this->patchJson(route('tasks.order'), [
            'days' => [
                ['date' => '2026-09-19', 'task_ids' => []],
                ['date' => '2026-09-20', 'task_ids' => [$taskId]],
            ],
        ])->assertNoContent();

        $this->assertTaskPlacement($taskId, '2026-09-20', 1);

        $this->patchJson(route('tasks.order'), [
            'days' => [['date' => '2026-09-19', 'task_ids' => [999]]],
        ])->assertJsonValidationErrors('days.0.task_ids.0');
    }

    public function test_unfinished_past_tasks_are_carried_over_to_the_end_of_today(): void
    {
        $this->createTask(['due_date' => '2026-09-23', 'position' => 1]);
        $unfinished = $this->createTask(['due_date' => '2026-09-20', 'status' => 'in_progress']);
        $completed = $this->createTask(['due_date' => '2026-09-20', 'status' => 'completed']);

        $this->post(route('tasks.carry-over'))->assertRedirect(route('tasks.index'));

        $this->assertTaskPlacement($unfinished, '2026-09-23', 2);
        $this->assertSame('2026-09-20', $this->findTask($completed)->dueDate->toDateString());
    }

    /**
     * Insert a task row and return its id.
     *
     * @param  array<string, mixed>  $attributes
     */
    private function createTask(array $attributes = []): int
    {
        return DB::table('tasks')->insertGetId([
            'title' => fake()->sentence(3),
            'description' => null,
            'status' => TaskStatus::NotStarted->value,
            'due_date' => '2026-09-23',
            'position' => 1,
            'created_at' => now(),
            'updated_at' => now(),
            ...$attributes,
        ]);
    }

    private function findTask(int $id): TaskEntity
    {
        return app(TaskRepository::class)->findOrFail($id);
    }

    private function assertTaskPlacement(int $id, string $expectedDate, int $expectedPosition): void
    {
        $task = $this->findTask($id);

        $this->assertSame([$expectedDate, $expectedPosition], [$task->dueDate->toDateString(), $task->position]);
    }
}
