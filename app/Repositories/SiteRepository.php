<?php

namespace App\Repositories;

use App\Entities\SiteEntity;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * All database access for the "sites" table, using the query builder.
 */
class SiteRepository
{
    /**
     * Get every site: pinned first, then the most opened from this app, then the most visited in the browser.
     *
     * @return Collection<int, SiteEntity>
     */
    public function all(): Collection
    {
        return $this->query()
            ->orderByDesc('is_pinned')
            ->orderByDesc('open_count')
            ->orderByRaw('browser_visit_count is null')
            ->orderByDesc('browser_visit_count')
            ->orderBy('title')
            ->get()
            ->map(SiteEntity::fromRow(...));
    }

    /**
     * Find a site by its id or abort with a 404.
     */
    public function findOrFail(int $id): SiteEntity
    {
        $row = $this->query()->where('id', $id)->first();

        return $row ? SiteEntity::fromRow($row) : abort(404);
    }

    /**
     * Get the saved sites with one of the given hosts, keyed by host.
     *
     * @param  list<string>  $hosts
     * @return Collection<string, SiteEntity>
     */
    public function getByHosts(array $hosts): Collection
    {
        return $this->query()
            ->whereIn('host', $hosts)
            ->get()
            ->map(SiteEntity::fromRow(...))
            ->keyBy('host');
    }

    /**
     * Insert a new site and return its id.
     *
     * @param  array{title: string, url: string, host: string, description?: ?string, is_pinned?: bool, browser_visit_count?: ?int}  $attributes
     */
    public function create(array $attributes): int
    {
        return $this->query()->insertGetId([...$attributes, 'created_at' => now(), 'updated_at' => now()]);
    }

    /**
     * Update the given columns of a site.
     *
     * @param  array{title?: string, url?: string, host?: string, description?: ?string, is_pinned?: bool}  $attributes
     */
    public function update(int $id, array $attributes): void
    {
        $this->query()->where('id', $id)->update([...$attributes, 'updated_at' => now()]);
    }

    /**
     * Count one more opening of the site from this app.
     */
    public function recordOpen(int $id): void
    {
        $this->query()->where('id', $id)->increment('open_count', 1, ['last_opened_at' => now()]);
    }

    public function delete(int $id): void
    {
        $this->query()->where('id', $id)->delete();
    }

    private function query(): Builder
    {
        return DB::table('sites');
    }
}
