<?php

namespace App\Entities;

use Carbon\CarbonImmutable;

/**
 * A single row of the "site_imports" table: sites read from the browser, waiting to be reviewed.
 */
final readonly class SiteImportEntity
{
    /**
     * @param  list<array{url: string, host: string, title: string, visit_count: ?int, sources: list<string>}>  $sites
     */
    public function __construct(
        public int $id,
        public array $sites,
        public ?CarbonImmutable $appliedAt,
    ) {}

    /**
     * Build an entity from a raw database row returned by the query builder.
     *
     * @param  object{id: int|string, sites: string, applied_at: ?string}  $row
     */
    public static function fromRow(object $row): self
    {
        return new self(
            id: (int) $row->id,
            sites: json_decode($row->sites, true, flags: JSON_THROW_ON_ERROR),
            appliedAt: $row->applied_at ? CarbonImmutable::parse($row->applied_at) : null,
        );
    }

    public function isApplied(): bool
    {
        return $this->appliedAt !== null;
    }
}
