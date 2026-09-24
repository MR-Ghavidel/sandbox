<?php

namespace App\Http\Controllers;

use App\Entities\TaskEntity;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Repositories\TaskRepository;
use App\TaskStatus;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TaskController extends Controller
{
    public function __construct(private TaskRepository $tasks) {}

    /**
     * Show the weekly (Saturday to Friday) task board.
     */
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'week' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $weekStart = CarbonImmutable::parse($validated['week'] ?? today())
            ->startOfWeek(CarbonInterface::SATURDAY);
        $weekEnd = $weekStart->addDays(6);

        $tasksByDate = $this->tasks->getBetween($weekStart, $weekEnd)
            ->groupBy(fn (TaskEntity $task): string => $task->dueDate->toDateString());

        $days = collect(range(0, 6))->map(fn (int $offset): array => [
            'date' => $weekStart->addDays($offset),
            'tasks' => $tasksByDate->get($weekStart->addDays($offset)->toDateString(), collect()),
        ]);

        return view('tasks.index', [
            'todayTasks' => $this->tasks->getBetween(today(), today()),
            'days' => $days,
            'weekStart' => $weekStart,
            'weekEnd' => $weekEnd,
            'statuses' => TaskStatus::cases(),
            'unfinishedPastTasksCount' => $this->tasks->countUnfinishedBefore(today()),
        ]);
    }

    /**
     * Store a new task at the end of its day.
     */
    public function store(StoreTaskRequest $request): RedirectResponse
    {
        $this->tasks->create(
            title: $request->validated('title'),
            description: $request->validated('description'),
            dueDate: CarbonImmutable::parse($request->validated('due_date')),
        );

        return back();
    }

    /**
     * Update a task's title, description or status.
     */
    public function update(UpdateTaskRequest $request, int $task): JsonResponse|RedirectResponse
    {
        $this->tasks->findOrFail($task);
        $this->tasks->update($task, $request->validated());

        if ($request->expectsJson()) {
            $updatedTask = $this->tasks->findOrFail($task);

            return response()->json([
                'id' => $updatedTask->id,
                'status' => $updatedTask->status->value,
                'status_label' => $updatedTask->status->label(),
            ]);
        }

        return back();
    }

    /**
     * Delete a task.
     */
    public function destroy(int $task): RedirectResponse
    {
        $this->tasks->findOrFail($task);
        $this->tasks->delete($task);

        return back();
    }
}
