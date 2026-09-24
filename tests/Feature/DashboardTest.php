<?php

namespace Tests\Feature;

use App\TaskStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Wednesday 1405/07/01 — its week runs from Saturday 2026-09-19 to Friday 2026-09-25.
        Carbon::setTestNow('2026-09-23 10:00:00');
    }

    public function test_home_page_shows_the_dashboard_inside_the_main_layout(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('dir="rtl"', false)
            ->assertSee('چهارشنبه ۱ مهر ۱۴۰۵')
            ->assertSee('href="'.route('tasks.index').'"', false)
            ->assertDontSee('Laravel has an incredibly rich ecosystem');
    }

    public function test_dashboard_shows_todays_tasks_and_week_progress(): void
    {
        $this->createTask('Done today', '2026-09-23', TaskStatus::Completed);
        $this->createTask('Pending today', '2026-09-23', TaskStatus::InProgress);
        $this->createTask('Earlier this week', '2026-09-19', TaskStatus::Completed);
        $this->createTask('Other week', '2026-09-26', TaskStatus::NotStarted);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Done today')
            ->assertSee('Pending today')
            ->assertDontSee('Earlier this week')
            ->assertDontSee('Other week')
            ->assertSee('style="width: 67%"', false);
    }

    private function createTask(string $title, string $dueDate, TaskStatus $status): void
    {
        DB::table('tasks')->insert([
            'title' => $title,
            'status' => $status->value,
            'due_date' => $dueDate,
            'position' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
