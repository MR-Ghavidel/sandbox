<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use finfo;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Finds and downloads the logo of a website.
 *
 * The page's own <link rel="icon"> / <link rel="apple-touch-icon"> tags are tried first (the
 * biggest one wins), then "/favicon.ico" at the site's root. Many sites have no /favicon.ico,
 * which is why the browser alone could not show their logo.
 */
class SiteIconFetcher
{
    /**
     * Image types that are kept, with the file extension used to store them.
     */
    private const EXTENSIONS = [
        'image/png' => 'png',
        'image/x-icon' => 'ico',
        'image/vnd.microsoft.icon' => 'ico',
        'image/svg+xml' => 'svg',
        'image/jpeg' => 'jpg',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
    ];

    private const MAX_BYTES = 512 * 1024;

    private const MAX_ATTEMPTS = 4;

    /**
     * Download the logo of the site at the given address.
     *
     * @return array{contents: string, extension: string}|null
     */
    public function fetch(string $url): ?array
    {
        foreach (array_slice($this->candidateUrls($url), 0, self::MAX_ATTEMPTS) as $iconUrl) {
            $icon = $this->download($iconUrl);

            if ($icon !== null) {
                return $icon;
            }
        }

        return null;
    }

    /**
     * Icon addresses to try, best first.
     *
     * @return list<string>
     */
    private function candidateUrls(string $url): array
    {
        $page = $this->get($url);
        $pageUrl = (string) ($page?->effectiveUri() ?? $url);
        $icons = $page?->successful() ? $this->iconLinks($page->body(), $pageUrl) : [];

        return array_values(array_unique([...$icons, $this->resolve('/favicon.ico', $pageUrl)]));
    }

    /**
     * The icons linked from the page, the biggest first. Apple touch icons and SVGs count as big.
     *
     * @return list<string>
     */
    private function iconLinks(string $html, string $pageUrl): array
    {
        $document = new DOMDocument;
        @$document->loadHTML($html, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);

        $icons = [];

        /** @var DOMElement $link */
        foreach ($document->getElementsByTagName('link') as $link) {
            $relations = preg_split('/\s+/', strtolower(trim($link->getAttribute('rel'))));
            $href = trim($link->getAttribute('href'));

            if ($href === '' || str_starts_with($href, 'data:') || array_intersect($relations, ['icon', 'apple-touch-icon', 'apple-touch-icon-precomposed']) === []) {
                continue;
            }

            preg_match('/(\d+)x\d+/', $link->getAttribute('sizes'), $size);
            $isLarge = in_array('apple-touch-icon', $relations, true)
                || str_contains($link->getAttribute('type'), 'svg')
                || str_ends_with(strtolower(parse_url($href, PHP_URL_PATH) ?: ''), '.svg');

            $icons[] = ['url' => $this->resolve($href, $pageUrl), 'size' => (int) ($size[1] ?? ($isLarge ? 180 : 16))];
        }

        usort($icons, fn (array $a, array $b): int => $b['size'] <=> $a['size']);

        return array_column($icons, 'url');
    }

    /**
     * @return array{contents: string, extension: string}|null
     */
    private function download(string $iconUrl): ?array
    {
        $response = $this->get($iconUrl);
        $contents = $response?->successful() ? $response->body() : '';

        if ($contents === '' || strlen($contents) > self::MAX_BYTES) {
            return null;
        }

        // Servers often send icons with a wrong type, so the bytes decide; the header is the fallback for SVGs.
        $mimeType = (new finfo(FILEINFO_MIME_TYPE))->buffer($contents);
        $extension = self::EXTENSIONS[$mimeType] ?? self::EXTENSIONS[strtolower(trim(explode(';', (string) $response->header('Content-Type'))[0]))] ?? null;

        if ($extension === 'svg' && ! str_contains($contents, '<svg')) {
            return null;
        }

        return $extension === null ? null : ['contents' => $contents, 'extension' => $extension];
    }

    private function get(string $url): ?Response
    {
        try {
            return Http::connectTimeout(3)
                ->timeout(5)
                ->withUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0 Safari/537.36')
                ->get($url);
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Turn a link found on a page into a full address: "/a.png", "a.png" and "//cdn.test/a.png" are all relative.
     */
    private function resolve(string $href, string $pageUrl): string
    {
        if (preg_match('#^https?://#i', $href) === 1) {
            return $href;
        }

        $page = parse_url($pageUrl);
        $scheme = $page['scheme'] ?? 'https';

        if (str_starts_with($href, '//')) {
            return "{$scheme}:{$href}";
        }

        $origin = $scheme.'://'.($page['host'] ?? '').(isset($page['port']) ? ':'.$page['port'] : '');

        $path = str_starts_with($href, '/') ? $href : (preg_replace('#/[^/]*$#', '/', $page['path'] ?? '/') ?: '/').$href;

        return $origin.$this->withoutDotSegments($path);
    }

    /**
     * "/guide/../img/./logo.png" → "/img/logo.png".
     */
    private function withoutDotSegments(string $path): string
    {
        [$path, $query] = array_pad(explode('?', $path, 2), 2, null);
        $segments = [];

        foreach (explode('/', $path) as $segment) {
            if ($segment === '..') {
                if (count($segments) > 1) {
                    array_pop($segments);
                }
            } elseif ($segment !== '.') {
                $segments[] = $segment;
            }
        }

        return implode('/', $segments).($query === null ? '' : "?{$query}");
    }
}
