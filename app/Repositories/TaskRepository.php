<?php

namespace App\Repositories;

use App\Entities\TaskEntity;
use App\TaskStatus;
use Carbon\CarbonInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * All database access for the "tasks" table, using the query builder.
 */
class TaskRepository
{
    /**
     * Get the tasks between two days (inclusive), ordered by day and position.
     *
     * @return Collection<int, TaskEntity>
     */
    public function getBetween(CarbonInterface $from, CarbonInterface $to): Collection
    {
        return $this->query()
            ->whereBetween('due_date', [$from->toDateString(), $to->toDateString()])
            ->orderBy('due_date')
            ->orderBy('position')
            ->orderBy('id')
            ->get()
            ->map(TaskEntity::fromRow(...));
    }

    /**
     * Find a task by its id.
     */
    public function find(int $id): ?TaskEntity
    {
        $row = $this->query()->where('id', $id)->first();

        return $row ? TaskEntity::fromRow($row) : null;
    }

    /**
     * Find a task by its id or abort with a 404.
     */
    public function findOrFail(int $id): TaskEntity
    {
        return $this->find($id) ?? abort(404);
    }

    /**
     * Insert a new task at the end of its day and return its id.
     */
    public function create(string $title, ?string $description, CarbonInterface $dueDate): int
    {
        return $this->query()->insertGetId([
            'title' => $title,
            'description' => $description,
            'status' => TaskStatus::NotStarted->value,
            'due_date' => $dueDate->toDateString(),
            'position' => $this->nextPositionFor($dueDate),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Update the given columns of a task.
     *
     * @param  array{title?: string, description?: ?string, status?: string, due_date?: string, position?: int}  $attributes
     */
    public function update(int $id, array $attributes): void
    {
        $this->query()->where('id', $id)->update([...$attributes, 'updated_at' => now()]);
    }

    /**
     * Move a task to a day and a position inside that day.
     */
    public function move(int $id, CarbonInterface $dueDate, int $position): void
    {
        $this->update($id, ['due_date' => $dueDate->toDateString(), 'position' => $position]);
    }

    /**
     * Delete a task.
     */
    public function delete(int $id): void
    {
        $this->query()->where('id', $id)->delete();
    }

    /**
     * Get the first free position at the end of the given day.
     */
    public function nextPositionFor(CarbonInterface $dueDate): int
    {
        return (int) $this->query()->where('due_date', $dueDate->toDateString())->max('position') + 1;
    }

    /**
     * Get the unfinished tasks of days before the given day.
     *
     * @return Collection<int, TaskEntity>
     */
    public function getUnfinishedBefore(CarbonInterface $day): Collection
    {
        return $this->unfinishedBeforeQuery($day)
            ->orderBy('due_date')
            ->orderBy('position')
            ->get()
            ->map(TaskEntity::fromRow(...));
    }

    /**
     * Count the unfinished tasks of days before the given day.
     */
    public function countUnfinishedBefore(CarbonInterface $day): int
    {
        return $this->unfinishedBeforeQuery($day)->count();
    }

    private function unfinishedBeforeQuery(CarbonInterface $day): Builder
    {
        return $this->query()
            ->where('status', '!=', TaskStatus::Completed->value)
            ->where('due_date', '<', $day->toDateString());
    }

    private function query(): Builder
    {
        return DB::table('tasks');
    }
}
