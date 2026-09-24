<?php

namespace App\Entities;

use App\Support\JalaliDate;
use App\TaskStatus;
use Carbon\CarbonImmutable;

/**
 * A single row of the "tasks" table.
 */
final readonly class TaskEntity
{
    public function __construct(
        public int $id,
        public string $title,
        public ?string $description,
        public TaskStatus $status,
        public CarbonImmutable $dueDate,
        public int $position,
        public ?CarbonImmutable $createdAt,
        public ?CarbonImmutable $updatedAt,
    ) {}

    /**
     * Build an entity from a raw database row returned by the query builder.
     *
     * @param  object{id: int|string, title: string, description: ?string, status: string, due_date: string, position: int|string, created_at: ?string, updated_at: ?string}  $row
     */
    public static function fromRow(object $row): self
    {
        return new self(
            id: (int) $row->id,
            title: $row->title,
            description: $row->description,
            status: TaskStatus::from($row->status),
            dueDate: CarbonImmutable::parse($row->due_date)->startOfDay(),
            position: (int) $row->position,
            createdAt: $row->created_at ? CarbonImmutable::parse($row->created_at) : null,
            updatedAt: $row->updated_at ? CarbonImmutable::parse($row->updated_at) : null,
        );
    }

    /**
     * Data the details and edit dialogs need, with dates already formatted in Jalali.
     *
     * @return array{title: string, description: ?string, status: string, status_label: string, due_date_label: string, created_at_label: ?string, updated_at_label: ?string}
     */
    public function toDialogData(): array
    {
        return [
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'due_date_label' => JalaliDate::format($this->dueDate, 'EEEE d MMMM y'),
            'created_at_label' => $this->createdAt ? JalaliDate::format($this->createdAt, 'd MMMM y، HH:mm') : null,
            'updated_at_label' => $this->updatedAt ? JalaliDate::format($this->updatedAt, 'd MMMM y، HH:mm') : null,
        ];
    }
}
