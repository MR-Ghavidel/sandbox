<?php

namespace App\Repositories;

use App\Entities\SiteImportEntity;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * All database access for the "site_imports" table, using the query builder.
 */
class SiteImportRepository
{
    /**
     * Store sites read from the browser and return the import's id.
     *
     * @param  list<array{url: string, host: string, title: string, visit_count: ?int, sources: list<string>}>  $sites
     */
    public function create(array $sites): int
    {
        return $this->query()->insertGetId([
            'sites' => json_encode($sites, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Find an import by its id or abort with a 404.
     */
    public function findOrFail(int $id): SiteImportEntity
    {
        $row = $this->query()->where('id', $id)->first();

        return $row ? SiteImportEntity::fromRow($row) : abort(404);
    }

    public function markApplied(int $id): void
    {
        $this->query()->where('id', $id)->update(['applied_at' => now(), 'updated_at' => now()]);
    }

    private function query(): Builder
    {
        return DB::table('site_imports');
    }
}
