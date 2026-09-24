<?php

namespace App\Http\Controllers;

use App\Entities\TaskEntity;
use App\Repositories\TaskRepository;
use App\TaskStatus;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Show the home page with an overview of today and this week.
     */
    public function __invoke(TaskRepository $tasks): View
    {
        $weekStart = CarbonImmutable::today()->startOfWeek(CarbonInterface::SATURDAY);
        $weekTasks = $tasks->getBetween($weekStart, $weekStart->addDays(6));
        $todayTasks = $weekTasks->filter(fn (TaskEntity $task): bool => $task->dueDate->isToday())->values();

        return view('dashboard.index', [
            'todayTasks' => $todayTasks,
            'todayCountsByStatus' => collect(TaskStatus::cases())->mapWithKeys(fn (TaskStatus $status): array => [
                $status->value => $todayTasks->where('status', $status)->count(),
            ]),
            'weekTotalCount' => $weekTasks->count(),
            'weekCompletedCount' => $weekTasks->where('status', TaskStatus::Completed)->count(),
            'unfinishedPastTasksCount' => $tasks->countUnfinishedBefore(today()),
        ]);
    }
}
