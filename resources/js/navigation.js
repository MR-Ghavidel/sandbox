/**
 * Soft navigation: following a link inside the app fetches the new page and replaces only its
 * content ([data-page]), the header title/actions and the drawer's links. The drawer, the header
 * and the loaded scripts stay, so the layout does not flash or reload.
 *
 * Anything unusual falls back to a normal full page load: other sites, new-tab/modifier clicks,
 * downloads, links marked [data-no-soft-navigation], errors, non-HTML responses and a newer build
 * of the CSS/JS. Forms are not intercepted; they submit normally.
 */
import { leaveWarning, runPageCleanups, runPageInitializers } from './support/page';

const desktopQuery = window.matchMedia('(min-width: 1024px)');
let activeRequest = null;
let renderedUrl = new URL(location.href);

history.scrollRestoration = 'manual';

const withoutHash = (url) => url.origin + url.pathname + url.search;

// The page scrolls inside the layout's scroll container, not the window (see layouts/app.blade.php).
const scrollContainer = () => document.querySelector('[data-scroll-container]') ?? document.scrollingElement;

const assetSignature = (root) =>
    [...root.querySelectorAll('head script[src], head link[rel="stylesheet"]')]
        .map((element) => element.getAttribute('src') ?? element.getAttribute('href'))
        .join('|');

const setProgress = (state) => {
    const bar = document.querySelector('[data-navigation-progress]');

    if (!bar) {
        return;
    }

    bar.dataset.state = state;

    if (state === 'done') {
        setTimeout(() => {
            if (bar.dataset.state === 'done') {
                bar.dataset.state = '';
            }
        }, 400);
    }
};

const isSoftNavigationLink = (link, event) => {
    if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
        return false;
    }

    if ((link.target && link.target !== '_self') || link.hasAttribute('download') || link.hasAttribute('data-no-soft-navigation')) {
        return false;
    }

    const url = new URL(link.href, location.href);

    if (url.origin !== location.origin || /\.[a-z0-9]{2,5}$/i.test(url.pathname)) {
        return false;
    }

    // A jump to an anchor on the same page is left to the browser.
    return !(url.hash && withoutHash(url) === withoutHash(renderedUrl));
};

const replaceContent = (newDocument) => {
    document.title = newDocument.title;

    const newPage = newDocument.querySelector('[data-page]');
    document.querySelector('[data-page]').replaceWith(newPage);

    for (const selector of ['[data-page-title]', '[data-page-actions]']) {
        const current = document.querySelector(selector);
        const replacement = newDocument.querySelector(selector);

        if (current && replacement) {
            current.replaceWith(replacement);
        }
    }

    // Keep the drawer's own scroll position while its links (active item) are refreshed.
    const currentNav = document.querySelector('[data-drawer-nav]');
    const newNav = newDocument.querySelector('[data-drawer-nav]');

    if (currentNav && newNav) {
        const { scrollTop } = currentNav;
        currentNav.replaceWith(newNav);
        newNav.scrollTop = scrollTop;
    }

    const csrfToken = newDocument.querySelector('meta[name="csrf-token"]')?.content;

    if (csrfToken) {
        document.querySelector('meta[name="csrf-token"]')?.setAttribute('content', csrfToken);
    }
};

/**
 * @param {string} url
 * @param {{isHistoryNavigation?: boolean}} options
 */
const navigate = async (url, { isHistoryNavigation = false } = {}) => {
    const warning = leaveWarning();

    if (warning && !window.confirm(warning)) {
        if (isHistoryNavigation) {
            history.pushState(history.state, '', renderedUrl.href);
        }

        return;
    }

    activeRequest?.abort();
    const request = new AbortController();
    activeRequest = request;
    setProgress('loading');

    try {
        const response = await fetch(url, { headers: { Accept: 'text/html' }, signal: request.signal });
        const isHtml = (response.headers.get('content-type') ?? '').includes('text/html');

        if (!response.ok || !isHtml) {
            throw new Error(`Soft navigation not possible (HTTP ${response.status})`);
        }

        const newDocument = new DOMParser().parseFromString(await response.text(), 'text/html');

        if (!newDocument.querySelector('[data-page]') || assetSignature(newDocument) !== assetSignature(document)) {
            throw new Error('Different layout or assets; loading the page fully');
        }

        if (request !== activeRequest) {
            return;
        }

        const finalUrl = new URL(response.url);
        finalUrl.hash = new URL(url, location.href).hash;

        if (!isHistoryNavigation) {
            history.replaceState({ ...history.state, scrollY: scrollContainer().scrollTop }, '');
            history.pushState({ scrollY: 0 }, '', finalUrl.href);
        }

        runPageCleanups();
        replaceContent(newDocument);
        renderedUrl = new URL(location.href);
        runPageInitializers();

        scrollContainer().scrollTo(0, isHistoryNavigation ? (history.state?.scrollY ?? 0) : 0);
        document.querySelector('[data-page] main')?.focus({ preventScroll: true });

        if (!desktopQuery.matches) {
            document.body.dataset.drawer = 'closed';
        }

        setProgress('done');
    } catch (error) {
        if (error.name !== 'AbortError') {
            window.location.href = url;
        }
    }
};

document.addEventListener('click', (event) => {
    const link = event.target.closest('a[href]');

    if (link && isSoftNavigationLink(link, event)) {
        event.preventDefault();
        navigate(link.href);
    }
});

window.addEventListener('popstate', () => {
    const url = new URL(location.href);

    // Only the hash changed: nothing to load.
    if (withoutHash(url) !== withoutHash(renderedUrl)) {
        navigate(url.href, { isHistoryNavigation: true });
    }
});
