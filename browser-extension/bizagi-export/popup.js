import { extractBizagiAttendance } from './extract.js';
import { collectBrowserSites } from './sites.js';

// config.js is written from the project's APP_URL by `php artisan bizagi-extension:configure`.
const { APP_URL: CONFIGURED_APP_URL } = await import('./config.js').catch(() => ({}));
const DEFAULT_APP_URL = CONFIGURED_APP_URL || 'https://sandbox.local';

const statusElement = document.getElementById('status');
const previewTable = document.getElementById('preview');
const sendButton = document.getElementById('send');
const sendSitesButton = document.getElementById('send-sites');
const appUrlInput = document.getElementById('app-url');

let extractedDays = [];

const showStatus = (message, type = 'info') => {
    statusElement.textContent = message;
    statusElement.className = `status ${type === 'info' ? '' : type}`;
};

/**
 * "sandbox.test/" → "https://sandbox.test". Throws when the value is not a URL.
 */
const normalizeUrl = (value) => {
    const trimmed = value.trim();
    const url = new URL(/^https?:\/\//i.test(trimmed) ? trimmed : `https://${trimmed}`);

    return `${url.origin}${url.pathname}`.replace(/\/+$/, '');
};

const getAppUrl = async () => {
    const { appUrl } = await chrome.storage.local.get('appUrl');

    return (appUrl || DEFAULT_APP_URL).replace(/\/+$/, '');
};

const renderPreview = (days) => {
    const body = previewTable.querySelector('tbody');
    body.replaceChildren();

    for (const day of days) {
        const row = body.insertRow();
        row.insertCell().textContent = day.date;

        const times = row.insertCell();
        times.className = 'times';
        times.textContent =
            day.holiday_label ??
            [
                ...day.pairs
                    .filter((pair) => pair.arrive || pair.leave)
                    .map((pair) => `${pair.arrive ?? '??'}–${pair.leave ?? '??'}`),
                ...day.leaves.map((leave) => `${leave.label} ${leave.from}–${leave.to}`),
            ].join('  ');
    }

    previewTable.hidden = days.length === 0;
};

const readBizagiTable = async () => {
    const [tab] = await chrome.tabs.query({ active: true, currentWindow: true });

    try {
        const results = await chrome.scripting.executeScript({
            target: { tabId: tab.id, allFrames: true },
            func: extractBizagiAttendance,
        });

        extractedDays = results.map((result) => result.result?.days ?? []).find((days) => days.length > 0) ?? [];
    } catch (error) {
        showStatus(`خواندن صفحه ممکن نشد: ${error.message}`, 'error');

        return;
    }

    if (extractedDays.length === 0) {
        showStatus('جدول ورود و خروج در این صفحه پیدا نشد. صفحه «جدول ورود و خروج» بیزاجی را باز کنید.', 'error');

        return;
    }

    showStatus(`${extractedDays.length} روز پیدا شد: از ${extractedDays[0].date} تا ${extractedDays.at(-1).date}`);
    renderPreview(extractedDays);
    sendButton.disabled = false;
};

/**
 * Posts data to the app and opens the review page it returns.
 * Must be called straight from a click handler: the permission request has to start before any await
 * to count as a user action. Firefox may not grant host permissions at install time, and optional API
 * permissions (history, ...) are always asked here; Chrome answers "granted" for what it already has.
 *
 * @param {{button: HTMLButtonElement, path: string, permissions?: string[], buildBody: (appUrl: string) => Promise<object>}} options
 */
const sendToApp = async ({ button, path, permissions = [], buildBody }) => {
    let appUrl;

    try {
        appUrl = appUrlInput.value.trim() ? normalizeUrl(appUrlInput.value) : DEFAULT_APP_URL;
    } catch {
        showStatus('آدرس سامانه معتبر نیست. آن را در تنظیمات اصلاح کنید.', 'error');

        return;
    }

    const permissionRequest = chrome.permissions.request({ origins: [`${new URL(appUrl).origin}/*`], permissions });

    button.disabled = true;
    showStatus('در حال ارسال...');

    try {
        if (!(await permissionRequest)) {
            throw new Error('اجازه دسترسی داده نشد');
        }

        const response = await fetch(`${appUrl}${path}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
            body: JSON.stringify(await buildBody(appUrl)),
        });

        if (!response.ok) {
            const body = await response.json().catch(() => ({}));
            throw new Error(body.message ?? `HTTP ${response.status}`);
        }

        const { preview_url: previewUrl } = await response.json();
        showStatus('ارسال شد. صفحه بررسی باز می‌شود...', 'success');
        await chrome.tabs.create({ url: previewUrl });
    } catch (error) {
        showStatus(`ارسال ناموفق بود: ${error.message}. آدرس سامانه را در تنظیمات بررسی کنید.`, 'error');
        button.disabled = false;
    }
};

sendButton.addEventListener('click', () =>
    sendToApp({
        button: sendButton,
        path: '/attendance-imports',
        buildBody: async () => ({ source: 'bizagi', days: extractedDays }),
    }),
);

sendSitesButton.addEventListener('click', () =>
    sendToApp({
        button: sendSitesButton,
        path: '/site-imports',
        permissions: ['topSites', 'history', 'bookmarks'],
        buildBody: async (appUrl) => {
            const sites = await collectBrowserSites({ excludeOrigin: new URL(appUrl).origin });

            if (sites.length === 0) {
                throw new Error('سایتی در مرورگر پیدا نشد');
            }

            return { sites };
        },
    }),
);

document.getElementById('save-url').addEventListener('click', async () => {
    // An empty field goes back to the address from the project's .env.
    if (!appUrlInput.value.trim()) {
        await chrome.storage.local.remove('appUrl');
        appUrlInput.value = DEFAULT_APP_URL;
        showStatus(`آدرس پیش‌فرض (${DEFAULT_APP_URL}) استفاده می‌شود.`, 'success');

        return;
    }

    let appUrl;

    try {
        appUrl = normalizeUrl(appUrlInput.value);
    } catch {
        showStatus('آدرس معتبر نیست.', 'error');

        return;
    }

    // The permission prompt can close the popup and drop everything after it, so the address is
    // saved first. The request still starts before any await to count as a user action.
    const permissionRequest = chrome.permissions.request({ origins: [`${new URL(appUrl).origin}/*`] });
    await chrome.storage.local.set({ appUrl });
    appUrlInput.value = appUrl;

    try {
        showStatus(
            (await permissionRequest) ? 'آدرس ذخیره شد.' : 'آدرس ذخیره شد، ولی اجازه دسترسی داده نشد؛ هنگام ارسال دوباره پرسیده می‌شود.',
            'success',
        );
    } catch (error) {
        showStatus(`آدرس ذخیره شد، ولی درخواست اجازه دسترسی خطا داد: ${error.message}`, 'error');
    }
});

appUrlInput.placeholder = DEFAULT_APP_URL;
appUrlInput.value = await getAppUrl();
readBizagiTable();
