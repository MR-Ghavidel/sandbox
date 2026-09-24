import { extractBizagiAttendance } from './extract.js';

const DEFAULT_APP_URL = 'https://sandbox.local';

const statusElement = document.getElementById('status');
const previewTable = document.getElementById('preview');
const sendButton = document.getElementById('send');
const appUrlInput = document.getElementById('app-url');

let extractedDays = [];

const showStatus = (message, type = 'info') => {
    statusElement.textContent = message;
    statusElement.className = `status ${type === 'info' ? '' : type}`;
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
            day.pairs
                .filter((pair) => pair.arrive || pair.leave)
                .map((pair) => `${pair.arrive ?? '??'}–${pair.leave ?? '??'}`)
                .join('  ');
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

sendButton.addEventListener('click', async () => {
    const appUrl = (appUrlInput.value.trim() || DEFAULT_APP_URL).replace(/\/+$/, '');

    // Firefox may not grant host permissions at install time. The request has to start directly in the
    // click handler (before any await) to count as a user action; Chrome simply answers "granted".
    const permissionRequest = chrome.permissions.request({ origins: [`${new URL(appUrl).origin}/*`] });

    sendButton.disabled = true;
    showStatus('در حال ارسال...');

    try {
        if (!(await permissionRequest)) {
            throw new Error('اجازه دسترسی به آدرس سامانه داده نشد');
        }

        const response = await fetch(`${appUrl}/attendance-imports`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
            body: JSON.stringify({ source: 'bizagi', days: extractedDays }),
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
        sendButton.disabled = false;
    }
});

document.getElementById('save-url').addEventListener('click', async () => {
    const appUrl = appUrlInput.value.trim().replace(/\/+$/, '');

    try {
        const origin = new URL(appUrl).origin;
        const isGranted = await chrome.permissions.request({ origins: [`${origin}/*`] });

        if (!isGranted) {
            showStatus('اجازه دسترسی به این آدرس داده نشد.', 'error');

            return;
        }

        await chrome.storage.local.set({ appUrl });
        showStatus('آدرس ذخیره شد.', 'success');
    } catch {
        showStatus('آدرس معتبر نیست.', 'error');
    }
});

appUrlInput.value = await getAppUrl();
readBizagiTable();
