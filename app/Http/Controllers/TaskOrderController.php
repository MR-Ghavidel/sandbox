<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReorderTasksRequest;
use App\Repositories\TaskRepository;
use Carbon\CarbonImmutable;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class TaskOrderController extends Controller
{
    /**
     * Persist the order (and day) of tasks after a drag and drop.
     */
    public function __invoke(ReorderTasksRequest $request, TaskRepository $tasks): Response
    {
        DB::transaction(function () use ($request, $tasks): void {
            foreach ($request->validated('days') as $day) {
                foreach ($day['task_ids'] as $index => $taskId) {
                    $tasks->move($taskId, CarbonImmutable::parse($day['date']), $index + 1);
                }
            }
        });

        return response()->noContent();
    }
}
