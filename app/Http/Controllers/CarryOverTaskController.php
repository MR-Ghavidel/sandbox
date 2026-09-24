<?php

namespace App\Http\Controllers;

use App\Repositories\TaskRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class CarryOverTaskController extends Controller
{
    /**
     * Move every unfinished task from past days to the end of today's list.
     */
    public function __invoke(TaskRepository $tasks): RedirectResponse
    {
        DB::transaction(function () use ($tasks): void {
            $nextPosition = $tasks->nextPositionFor(today());

            foreach ($tasks->getUnfinishedBefore(today()) as $task) {
                $tasks->move($task->id, today(), $nextPosition++);
            }
        });

        return to_route('tasks.index');
    }
}
