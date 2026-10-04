<?php

namespace App\Http\Controllers;

use App\Entities\SiteEntity;
use App\Http\Requests\StoreSiteRequest;
use App\Repositories\SiteRepository;
use App\Support\SiteIconFetcher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class SiteController extends Controller
{
    public function __construct(private SiteRepository $sites) {}

    /**
     * Show the saved sites, pinned and most used first.
     */
    public function index(): View
    {
        return view('sites.index', ['sites' => $this->sites->all()]);
    }

    public function store(StoreSiteRequest $request): RedirectResponse
    {
        $this->sites->create($request->siteAttributes());

        return to_route('sites.index')->with('status', 'سایت اضافه شد.');
    }

    public function edit(int $site): View
    {
        return view('sites.edit', ['site' => $this->sites->findOrFail($site)]);
    }

    public function update(StoreSiteRequest $request, int $site): RedirectResponse
    {
        $savedSite = $this->sites->findOrFail($site);
        $attributes = $request->siteAttributes();

        // Another website means another logo, so it is looked for again.
        if ($attributes['host'] !== $savedSite->host) {
            $this->forgetIcon($savedSite);
            $attributes += ['icon_path' => null, 'icon_checked_at' => null];
        }

        $this->sites->update($site, $attributes);

        return to_route('sites.index')->with('status', 'سایت ذخیره شد.');
    }

    /**
     * Pin or unpin a site from the list.
     */
    public function pin(Request $request, int $site): RedirectResponse
    {
        $this->sites->findOrFail($site);
        $this->sites->update($site, ['is_pinned' => $request->validate(['is_pinned' => ['required', 'boolean']])['is_pinned']]);

        return back();
    }

    /**
     * Count the visit and go to the site, so the most used sites move up the list.
     */
    public function open(int $site): RedirectResponse
    {
        $savedSite = $this->sites->findOrFail($site);
        $this->sites->recordOpen($site);

        return redirect()->away($savedSite->url);
    }

    /**
     * Serve the site's logo. The first request downloads it from the site, later ones are served from disk.
     */
    public function icon(int $site, SiteIconFetcher $fetcher): Response
    {
        $savedSite = $this->sites->findOrFail($site);
        $iconPath = $savedSite->needsIconCheck() ? $this->downloadIcon($savedSite, $fetcher) : $savedSite->iconPath;

        abort_if($iconPath === null || ! Storage::disk('local')->exists($iconPath), 404);

        return Storage::disk('local')->response($iconPath, headers: [
            'Cache-Control' => 'public, max-age=604800',
            // An SVG logo is a document that could run scripts if opened on its own.
            'Content-Security-Policy' => "default-src 'none'; style-src 'unsafe-inline'; sandbox",
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function destroy(int $site): RedirectResponse
    {
        $this->forgetIcon($this->sites->findOrFail($site));
        $this->sites->delete($site);

        return to_route('sites.index')->with('status', 'سایت حذف شد.');
    }

    private function downloadIcon(SiteEntity $site, SiteIconFetcher $fetcher): ?string
    {
        $this->forgetIcon($site);

        $icon = $fetcher->fetch($site->url);
        $iconPath = $icon === null ? null : "site-icons/{$site->id}.{$icon['extension']}";

        if ($icon !== null) {
            Storage::disk('local')->put($iconPath, $icon['contents']);
        }

        $this->sites->update($site->id, ['icon_path' => $iconPath, 'icon_checked_at' => now()]);

        return $iconPath;
    }

    private function forgetIcon(SiteEntity $site): void
    {
        if ($site->iconPath !== null) {
            Storage::disk('local')->delete($site->iconPath);
        }
    }
}
