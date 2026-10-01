export const HISTORY_DAYS = 90;
const HISTORY_LIMIT = 150;
const MIN_HISTORY_VISITS = 3;

/**
 * Reads websites from the browser for the "sites" page: the most visited sites (topSites), the history
 * of the last HISTORY_DAYS days grouped by site, and bookmarks. Needs the optional "topSites", "history"
 * and "bookmarks" permissions; a source without permission is skipped.
 *
 * @returns {Promise<Array<{url: string, title: string, visit_count: ?number, sources: string[]}>>}
 */
export async function collectBrowserSites({ excludeOrigin = null } = {}) {
    const sites = [];
    const granted = await chrome.permissions.getAll();
    const has = (permission) => granted.permissions?.includes(permission);

    if (has('topSites')) {
        for (const site of await chrome.topSites.get()) {
            sites.push({ url: site.url, title: site.title ?? '', visit_count: null, sources: ['top_sites'] });
        }
    }

    if (has('history')) {
        const items = await chrome.history.search({
            text: '',
            startTime: Date.now() - HISTORY_DAYS * 24 * 60 * 60 * 1000,
            maxResults: 20000,
        });

        sites.push(...groupHistoryBySite(items));
    }

    if (has('bookmarks')) {
        const walk = (nodes) =>
            nodes.flatMap((node) => (node.url ? [node] : walk(node.children ?? [])));

        for (const bookmark of walk(await chrome.bookmarks.getTree())) {
            sites.push({ url: bookmark.url, title: bookmark.title ?? '', visit_count: null, sources: ['bookmarks'] });
        }
    }

    return sites.filter((site) => /^https?:\/\//i.test(site.url) && (!excludeOrigin || new URL(site.url).origin !== excludeOrigin));
}

/**
 * History has one item per page; a site is its host. Visits are summed, the address becomes the site's
 * home page and the title is taken from the home page if it was visited, else from the most visited page.
 */
export function groupHistoryBySite(items) {
    const sitesByHost = new Map();

    for (const item of items) {
        let url;

        try {
            url = new URL(item.url);
        } catch {
            continue;
        }

        if (!/^https?:$/.test(url.protocol)) {
            continue;
        }

        const host = url.hostname.replace(/^www\./, '');
        const site = sitesByHost.get(host) ?? { origin: url.origin, visits: 0, homeTitle: null, bestTitle: null, bestVisits: -1 };

        site.visits += item.visitCount ?? 0;

        if (url.pathname === '/' && item.title) {
            site.homeTitle = item.title;
        }

        if ((item.visitCount ?? 0) > site.bestVisits && item.title) {
            site.bestTitle = item.title;
            site.bestVisits = item.visitCount ?? 0;
        }

        sitesByHost.set(host, site);
    }

    return [...sitesByHost.values()]
        .filter((site) => site.visits >= MIN_HISTORY_VISITS)
        .sort((first, second) => second.visits - first.visits)
        .slice(0, HISTORY_LIMIT)
        .map((site) => ({
            url: `${site.origin}/`,
            title: site.homeTitle ?? site.bestTitle ?? '',
            visit_count: site.visits,
            sources: ['history'],
        }));
}
