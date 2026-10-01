<?php

namespace App\Http\Requests;

use App\Entities\SiteEntity;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Sites read from the browser by the extension: most visited (topSites), history and bookmarks.
 */
class StoreSiteImportRequest extends FormRequest
{
    public const SOURCES = ['top_sites', 'history', 'bookmarks'];

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'sites' => ['required', 'array', 'min:1', 'max:1000'],
            'sites.*.url' => ['required', 'string', 'max:2048'],
            'sites.*.title' => ['nullable', 'string', 'max:1000'],
            'sites.*.visit_count' => ['nullable', 'integer', 'min:0'],
            'sites.*.sources' => ['required', 'array', 'min:1'],
            'sites.*.sources.*' => ['string', 'in:'.implode(',', self::SOURCES)],
        ];
    }

    /**
     * The received sites, one per host: non-web addresses are dropped, and duplicates are merged
     * (sources combined, highest visit count kept, first URL and title kept).
     *
     * @return list<array{url: string, host: string, title: string, visit_count: ?int, sources: list<string>}>
     */
    public function normalizedSites(): array
    {
        return collect($this->validated('sites'))
            ->filter(fn (array $site): bool => preg_match('#^https?://#i', $site['url']) === 1 && SiteEntity::hostOf($site['url']) !== null)
            ->groupBy(fn (array $site): string => SiteEntity::hostOf($site['url']))
            ->map(function ($sites, string $host): array {
                $first = $sites->first();
                $visitCounts = $sites->pluck('visit_count')->filter(fn (?int $count): bool => $count !== null);

                return [
                    'url' => $first['url'],
                    'host' => $host,
                    'title' => mb_substr(trim((string) ($sites->pluck('title')->first(fn (?string $title): bool => filled($title)) ?? $host)), 0, 255),
                    'visit_count' => $visitCounts->isEmpty() ? null : $visitCounts->max(),
                    'sources' => $sites->pluck('sources')->flatten()->unique()->values()->all(),
                ];
            })
            ->values()
            ->all();
    }
}
