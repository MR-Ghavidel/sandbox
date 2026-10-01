<?php

namespace App\Entities;

use App\Support\JalaliDate;
use App\Support\Markdown;
use App\Support\TextDirection;
use Carbon\CarbonImmutable;

/**
 * A single row of the "notes" table. The body is Markdown.
 */
final readonly class NoteEntity
{
    public function __construct(
        public int $id,
        public string $title,
        public ?string $body,
        public bool $isPinned,
        public ?CarbonImmutable $createdAt,
        public ?CarbonImmutable $updatedAt,
    ) {}

    /**
     * Build an entity from a raw database row returned by the query builder.
     *
     * @param  object{id: int|string, title: string, body: ?string, is_pinned: int|string|bool, created_at: ?string, updated_at: ?string}  $row
     */
    public static function fromRow(object $row): self
    {
        return new self(
            id: (int) $row->id,
            title: $row->title,
            body: $row->body,
            isPinned: (bool) $row->is_pinned,
            createdAt: $row->created_at ? CarbonImmutable::parse($row->created_at) : null,
            updatedAt: $row->updated_at ? CarbonImmutable::parse($row->updated_at) : null,
        );
    }

    public function bodyHtml(): string
    {
        return Markdown::render($this->body);
    }

    public function excerpt(): string
    {
        return Markdown::excerpt($this->body);
    }

    public function titleDirection(): string
    {
        return TextDirection::detect($this->title);
    }

    public function excerptDirection(): string
    {
        return TextDirection::detect($this->excerpt());
    }

    public function updatedAtLabel(): ?string
    {
        return $this->updatedAt ? JalaliDate::format($this->updatedAt, 'd MMMM y، HH:mm') : null;
    }
}
