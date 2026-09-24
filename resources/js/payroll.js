import { onBeforeLeave, onPageLoad } from './support/page';

/**
 * Payroll page:
 * - Auto-saves a day of the attendance table whenever one of its fields changes,
 *   shows the save state per row (… saving, ✓ saved, ! error) and refreshes the summary.
 * - Live "total" per day while times are being typed (the server's value replaces it on save).
 * - Thousands separators in amount inputs.
 */
const persianDigits = '۰۱۲۳۴۵۶۷۸۹';
const arabicDigits = '٠١٢٣٤٥٦٧٨٩';

const toLatinDigits = (value) =>
    value
        .replace(/[۰-۹]/g, (digit) => persianDigits.indexOf(digit))
        .replace(/[٠-٩]/g, (digit) => arabicDigits.indexOf(digit));

const toMinutes = (value) => {
    const match = toLatinDigits(value.trim()).match(/^(\d{1,2})[:.]?(\d{2})$/);

    return match ? Number(match[1]) * 60 + Number(match[2]) : null;
};

const formatMinutes = (minutes) =>
    `${Math.floor(minutes / 60)}:${String(minutes % 60).padStart(2, '0')}`.replace(/\d/g, (digit) => persianDigits[digit]);

/* ---------- Live row total ---------- */

const refreshRowTotal = (row) => {
    const arrives = row.querySelectorAll('[data-time-input="arrive"]');
    const leaves = row.querySelectorAll('[data-time-input="leave"]');
    let total = 0;

    arrives.forEach((arriveInput, index) => {
        const arrive = toMinutes(arriveInput.value);
        const leave = toMinutes(leaves[index].value);

        if (arrive !== null && leave !== null) {
            total += leave >= arrive ? leave - arrive : leave + 24 * 60 - arrive;
        }
    });

    row.querySelector('[data-row-total]').textContent = total > 0 ? formatMinutes(total) : '';
};

/* ---------- Auto-save ---------- */

const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
const saveStateSymbols = { idle: '', saving: '…', saved: '✓', error: '!' };
const rowSaves = new WeakMap();

const setSaveState = (row, state, message = '') => {
    const indicator = row.querySelector('[data-save-state]');
    indicator.dataset.state = state;
    indicator.textContent = saveStateSymbols[state];
    indicator.title = message || { saving: 'در حال ذخیره...', saved: 'ذخیره شد', error: 'ذخیره نشد' }[state] || '';
};

const noteOf = (row) => {
    const select = row.querySelector('[data-note-select]');

    return select.value === '__other' ? row.querySelector('[data-note-other]').value.trim() : select.value;
};

const rowPayload = (row) => {
    const payload = { is_work_day: row.querySelector('[data-field="is_work_day"]').checked, note: noteOf(row), arrive: {}, leave: {} };

    row.querySelectorAll('[data-time-input]').forEach((input) => {
        const [type, pair] = input.dataset.field.split('.');
        payload[type][pair] = input.value;
    });

    return payload;
};

const applySavedDay = (row, day, sentPayload) => {
    // Show the normalized times ("830" becomes "08:30"), but never overwrite a field that was
    // edited while this save was running: its newer value is sent by the queued save.
    day.pairs.forEach((pair, index) => {
        for (const type of ['arrive', 'leave']) {
            const input = row.querySelector(`[data-field="${type}.${index + 1}"]`);

            if (input.value === sentPayload[type][index + 1]) {
                input.value = pair[type] ?? '';
                input.removeAttribute('aria-invalid');
            }
        }
    });
    row.dataset.workDay = day.is_work_day ? '1' : '0';
    row.querySelector('[data-row-total]').textContent = day.total_label;
    row.querySelector('[data-incomplete-badge]').classList.toggle('hidden', !day.is_incomplete);
    row.querySelector('[data-off-day-work-badge]').classList.toggle('hidden', !day.is_off_day_work);

    document.querySelector('[data-payroll-summary]').innerHTML = day.summary_html;
    document.querySelector('[data-payroll-breakdown]').innerHTML = day.breakdown_html;
};

const showValidationErrors = (row, errors) => {
    Object.keys(errors).forEach((key) => {
        row.querySelector(`[data-field="${key}"]`)?.setAttribute('aria-invalid', 'true');
    });

    setSaveState(row, 'error', Object.values(errors).flat()[0]);
};

const saveRow = async (row) => {
    const state = rowSaves.get(row) ?? { isSaving: false, isQueued: false };
    rowSaves.set(row, state);

    // Changes made while a save is running are sent right after it finishes.
    if (state.isSaving) {
        state.isQueued = true;

        return;
    }

    state.isSaving = true;
    setSaveState(row, 'saving');

    const payload = rowPayload(row);

    try {
        const response = await fetch(row.dataset.saveUrl, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify(payload),
        });

        if (response.status === 422) {
            showValidationErrors(row, (await response.json()).errors ?? {});
        } else if (!response.ok) {
            setSaveState(row, 'error', `ذخیره نشد (HTTP ${response.status})`);
        } else {
            applySavedDay(row, await response.json(), payload);
            setSaveState(row, state.isQueued ? 'saving' : 'saved');
        }
    } catch {
        setSaveState(row, 'error', 'ارتباط با سرور برقرار نشد');
    } finally {
        state.isSaving = false;

        if (state.isQueued) {
            state.isQueued = false;
            saveRow(row);
        }
    }
};

const hasPendingSaves = () =>
    [...document.querySelectorAll('[data-attendance-row]')].some((row) => rowSaves.get(row)?.isSaving);

document.addEventListener('input', (event) => {
    const row = event.target.closest('[data-attendance-row]');

    if (row && event.target.matches('[data-time-input]')) {
        refreshRowTotal(row);
    }
});

// "change" fires when a text field loses focus after editing, and immediately for checkboxes and selects.
document.addEventListener('change', (event) => {
    const row = event.target.closest('[data-attendance-row]');

    if (!row) {
        return;
    }

    const noteSelect = event.target.closest('[data-note-select]');

    if (noteSelect) {
        const otherInput = row.querySelector('[data-note-other]');
        otherInput.classList.toggle('hidden', noteSelect.value !== '__other');

        if (noteSelect.value === '__other') {
            otherInput.focus();

            return; // Saved when the custom note is typed and the field is left.
        }

        if (noteSelect.selectedOptions[0]?.dataset.dayOff === '1') {
            row.querySelector('[data-field="is_work_day"]').checked = false;
        }
    }

    saveRow(row);
});

onBeforeLeave(() => (hasPendingSaves() ? 'تغییرات هنوز در حال ذخیره است. صفحه را ترک می‌کنید؟' : null));

window.addEventListener('beforeunload', (event) => {
    if (hasPendingSaves()) {
        event.preventDefault();
    }
});

/* ---------- Excel upload ---------- */

// Choosing files in the "ورود از اکسل" button uploads them right away.
document.addEventListener('change', (event) => {
    const input = event.target.closest('input[type="file"][data-auto-submit]');

    if (!input || input.files.length === 0) {
        return;
    }

    const label = input.closest('label');
    label?.classList.add('pointer-events-none', 'opacity-60');
    label?.setAttribute('aria-busy', 'true');
    label?.querySelector('[data-upload-label]')?.replaceChildren('در حال خواندن...');

    input.form.requestSubmit();
});

/* ---------- Thousands separators ---------- */

const formatAmount = (digits) => digits.replace(/\B(?=(\d{3})+(?!\d))/g, ',');

onPageLoad(() => {
    document.querySelectorAll('[data-amount-input]').forEach((input) => {
        input.value = formatAmount(toLatinDigits(input.value).replace(/\D/g, ''));
    });
});

document.addEventListener('input', (event) => {
    const input = event.target.closest('[data-amount-input]');

    if (!input) {
        return;
    }

    // Keep the caret after the same number of digits once the separators move.
    const digitsBeforeCaret = toLatinDigits(input.value.slice(0, input.selectionStart)).replace(/\D/g, '').length;
    const formatted = formatAmount(toLatinDigits(input.value).replace(/\D/g, ''));
    input.value = formatted;

    let caret = 0;

    for (let seenDigits = 0; caret < formatted.length && seenDigits < digitsBeforeCaret; caret++) {
        if (/\d/.test(formatted[caret])) {
            seenDigits++;
        }
    }

    input.setSelectionRange(caret, caret);
});
