<?php

namespace App\Repositories;

use App\Entities\NoteEntity;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * All database access for the "notes" table, using the query builder.
 */
class NoteRepository
{
    /**
     * Get the notes, pinned first and then the most recently updated, optionally filtered by a search text.
     *
     * @return Collection<int, NoteEntity>
     */
    public function search(?string $text = null): Collection
    {
        return $this->query()
            ->when(filled($text), fn (Builder $query) => $query->where(fn (Builder $query) => $query
                ->where('title', 'like', '%'.$text.'%')
                ->orWhere('body', 'like', '%'.$text.'%')))
            ->orderByDesc('is_pinned')
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->get()
            ->map(NoteEntity::fromRow(...));
    }

    /**
     * Find a note by its id or abort with a 404.
     */
    public function findOrFail(int $id): NoteEntity
    {
        $row = $this->query()->where('id', $id)->first();

        return $row ? NoteEntity::fromRow($row) : abort(404);
    }

    /**
     * Insert a new note and return its id.
     */
    public function create(string $title, ?string $body, bool $isPinned = false): int
    {
        return $this->query()->insertGetId([
            'title' => $title,
            'body' => $body,
            'is_pinned' => $isPinned,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Update the given columns of a note.
     *
     * @param  array{title?: string, body?: ?string, is_pinned?: bool}  $attributes
     */
    public function update(int $id, array $attributes): void
    {
        $this->query()->where('id', $id)->update([...$attributes, 'updated_at' => now()]);
    }

    /**
     * Pin or unpin a note without changing its "updated" time, so pinning does not reorder the list.
     */
    public function setPinned(int $id, bool $isPinned): void
    {
        $this->query()->where('id', $id)->update(['is_pinned' => $isPinned]);
    }

    public function delete(int $id): void
    {
        $this->query()->where('id', $id)->delete();
    }

    private function query(): Builder
    {
        return DB::table('notes');
    }
}
