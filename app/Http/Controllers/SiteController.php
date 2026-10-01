<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSiteRequest;
use App\Repositories\SiteRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

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
        $this->sites->findOrFail($site);
        $this->sites->update($site, $request->siteAttributes());

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

    public function destroy(int $site): RedirectResponse
    {
        $this->sites->findOrFail($site);
        $this->sites->delete($site);

        return to_route('sites.index')->with('status', 'سایت حذف شد.');
    }
}
