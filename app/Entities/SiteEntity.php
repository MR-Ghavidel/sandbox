<?php

namespace App\Entities;

use App\Support\TextDirection;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

/**
 * A single row of the "sites" table: a favorite or frequently used website.
 */
final readonly class SiteEntity
{
    public function __construct(
        public int $id,
        public string $title,
        public string $url,
        public string $host,
        public ?string $description,
        public bool $isPinned,
        public int $openCount,
        public ?int $browserVisitCount,
        public ?CarbonImmutable $lastOpenedAt,
    ) {}

    /**
     * Build an entity from a raw database row returned by the query builder.
     *
     * @param  object{id: int|string, title: string, url: string, host: string, description: ?string, is_pinned: int|string|bool, open_count: int|string, browser_visit_count: int|string|null, last_opened_at: ?string}  $row
     */
    public static function fromRow(object $row): self
    {
        return new self(
            id: (int) $row->id,
            title: $row->title,
            url: $row->url,
            host: $row->host,
            description: $row->description,
            isPinned: (bool) $row->is_pinned,
            openCount: (int) $row->open_count,
            browserVisitCount: $row->browser_visit_count === null ? null : (int) $row->browser_visit_count,
            lastOpenedAt: $row->last_opened_at ? CarbonImmutable::parse($row->last_opened_at) : null,
        );
    }

    /**
     * The key used to find duplicates: "https://www.GitHub.com/x" → "github.com".
     */
    public static function hostOf(string $url): ?string
    {
        $host = parse_url($url, PHP_URL_HOST);

        return is_string($host) && $host !== '' ? Str::of($host)->lower()->chopStart('www.')->toString() : null;
    }

    public function titleDirection(): string
    {
        return TextDirection::detect($this->title);
    }

    public function descriptionDirection(): string
    {
        return TextDirection::detect($this->description);
    }

    /**
     * The first letter of the title, shown when the site's icon cannot be loaded.
     */
    public function initial(): string
    {
        return Str::upper(mb_substr($this->title, 0, 1));
    }
}
