<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSiteImportRequest;
use App\Repositories\SiteImportRepository;
use App\Repositories\SiteRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SiteImportController extends Controller
{
    public function __construct(
        private SiteImportRepository $imports,
        private SiteRepository $sites,
    ) {}

    /**
     * Receive sites read from the browser by the extension and return the review page's URL.
     */
    public function store(StoreSiteImportRequest $request): JsonResponse
    {
        $sites = $request->normalizedSites();

        if ($sites === []) {
            return response()->json(['message' => 'هیچ آدرس وبی در داده‌های ارسالی نبود.'], 422);
        }

        return response()->json(['preview_url' => route('site-imports.show', $this->imports->create($sites))], 201);
    }

    /**
     * List the received sites to choose which ones to save. Sites that are already saved are marked.
     */
    public function show(int $import): View
    {
        $siteImport = $this->imports->findOrFail($import);

        return view('site-imports.show', [
            'import' => $siteImport,
            'sites' => collect($siteImport->sites)->sortByDesc(fn (array $site): int => $site['visit_count'] ?? -1),
            'savedSites' => $this->sites->getByHosts(array_column($siteImport->sites, 'host')),
        ]);
    }

    /**
     * Save the chosen sites. Hosts saved in the meantime are skipped.
     */
    public function apply(Request $request, int $import): RedirectResponse
    {
        $siteImport = $this->imports->findOrFail($import);

        if ($siteImport->isApplied()) {
            return back()->with('status', 'این ورودی قبلاً اعمال شده است.');
        }

        $chosenHosts = $request->validate([
            'hosts' => ['required', 'array', 'min:1'],
            'hosts.*' => ['string'],
        ], ['hosts.required' => 'هیچ سایتی انتخاب نشده است.'])['hosts'];

        $chosenSites = collect($siteImport->sites)->whereIn('host', $chosenHosts);
        $savedHosts = $this->sites->getByHosts($chosenSites->pluck('host')->all())->keys();
        $newSites = $chosenSites->whereNotIn('host', $savedHosts);

        DB::transaction(function () use ($newSites, $siteImport): void {
            foreach ($newSites as $site) {
                $this->sites->create([
                    'title' => $site['title'],
                    'url' => $site['url'],
                    'host' => $site['host'],
                    'browser_visit_count' => $site['visit_count'],
                ]);
            }

            $this->imports->markApplied($siteImport->id);
        });

        return to_route('sites.index')->with('status', "{$newSites->count()} سایت اضافه شد.");
    }
}
