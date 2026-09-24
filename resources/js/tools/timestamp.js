import { gregorianToJalali, jalaliMonthLength, jalaliToGregorian } from '../support/jalali';
import { onPageLoad } from '../support/page';

onPageLoad(() => {
    const tool = document.querySelector('[data-timestamp-tool]');

    if (!tool) {
        return;
    }

    const timezoneSelect = tool.querySelector('[data-timezone]');
    const timestampInput = tool.querySelector('[data-timestamp-input]');
    const datePart = (name) => tool.querySelector(`[data-date-part="${name}"]`);
    const result = (name) => tool.querySelector(`[data-result="${name}"]`);

    const timezone = () => (timezoneSelect.value === 'local' ? Intl.DateTimeFormat().resolvedOptions().timeZone : timezoneSelect.value);

    // The calendar the date fields are currently filled in; updated only after converting them on a switch.
    let currentCalendar = tool.querySelector('[data-calendar]:checked').value;
    const calendar = () => currentCalendar;

    const toLatinDigits = (value) =>
        value.replace(/[۰-۹]/g, (digit) => '۰۱۲۳۴۵۶۷۸۹'.indexOf(digit)).replace(/[٠-٩]/g, (digit) => '٠١٢٣٤٥٦٧٨٩'.indexOf(digit));

    const dateTimeOptions = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit', second: '2-digit', hourCycle: 'h23' };

    // Built from parts, because Intl's own Persian order ("۱۴۰۴ مهر ۲, چهارشنبه") reads awkwardly.
    const formatJalali = (date) => {
        const parts = Object.fromEntries(
            new Intl.DateTimeFormat('fa-IR-u-ca-persian', { ...dateTimeOptions, timeZone: timezone() })
                .formatToParts(date)
                .map(({ type, value }) => [type, value]),
        );

        return `${parts.weekday} ${parts.day} ${parts.month} ${parts.year}، ساعت ${parts.hour}:${parts.minute}:${parts.second}`;
    };
    const formatGregorian = (date) => new Intl.DateTimeFormat('en-GB', { ...dateTimeOptions, timeZone: timezone() }).format(date);

    const formatRelative = (date) => {
        const seconds = Math.round((date.getTime() - Date.now()) / 1000);
        const units = [['year', 31536000], ['month', 2592000], ['day', 86400], ['hour', 3600], ['minute', 60], ['second', 1]];
        const [unit, size] = units.find(([, unitSeconds]) => Math.abs(seconds) >= unitSeconds) ?? ['second', 1];

        return new Intl.RelativeTimeFormat('fa', { numeric: 'auto' }).format(Math.round(seconds / size), unit);
    };

    /**
     * Wall-clock parts of a moment in the chosen time zone.
     */
    const partsIn = (date) => {
        const parts = Object.fromEntries(
            new Intl.DateTimeFormat('en-US', { timeZone: timezone(), hourCycle: 'h23', year: 'numeric', month: 'numeric', day: 'numeric', hour: 'numeric', minute: 'numeric', second: 'numeric' })
                .formatToParts(date)
                .map(({ type, value }) => [type, Number(value)]),
        );

        return { year: parts.year, month: parts.month, day: parts.day, hour: parts.hour % 24, minute: parts.minute, second: parts.second };
    };

    /**
     * The moment at which the clock in the chosen time zone shows the given wall-clock time.
     */
    const zonedTimeToDate = ({ year, month, day, hour, minute, second }) => {
        const wallClockAsUtc = Date.UTC(year, month - 1, day, hour, minute, second);
        let guess = wallClockAsUtc;

        // Two passes settle the offset, also around daylight-saving changes.
        for (let pass = 0; pass < 2; pass++) {
            const shown = partsIn(new Date(guess));
            const offset = Date.UTC(shown.year, shown.month - 1, shown.day, shown.hour, shown.minute, shown.second) - guess;
            guess = wallClockAsUtc - offset;
        }

        return new Date(guess);
    };

    /* ---------- Now ---------- */

    const tick = () => {
        const now = new Date();
        tool.querySelector('[data-now-timestamp]').textContent = Math.floor(now.getTime() / 1000);
        tool.querySelector('[data-now-label]').textContent = formatJalali(now);
    };

    /* ---------- Timestamp → date ---------- */

    const showError = (selector, message) => {
        const element = tool.querySelector(selector);
        element.textContent = message;
        element.classList.toggle('hidden', message === '');
    };

    const convertTimestamp = () => {
        const digits = toLatinDigits(timestampInput.value).replace(/[\s,٬]/g, '');
        const resultList = tool.querySelector('[data-timestamp-result]');

        if (digits === '') {
            resultList.classList.add('hidden');
            showError('[data-timestamp-error]', '');

            return;
        }

        if (!/^-?\d+(\.\d+)?$/.test(digits)) {
            resultList.classList.add('hidden');
            showError('[data-timestamp-error]', 'فقط عدد وارد کنید.');

            return;
        }

        const integerDigits = digits.replace(/^-/, '').split('.')[0].length;
        const [divisor, unit] = integerDigits >= 15 ? [1000, 'میکروثانیه'] : integerDigits >= 12 ? [1, 'میلی‌ثانیه'] : [0.001, 'ثانیه'];
        const date = new Date(Number(digits) / divisor);

        if (Number.isNaN(date.getTime())) {
            resultList.classList.add('hidden');
            showError('[data-timestamp-error]', 'این عدد خارج از بازه تاریخ‌های قابل نمایش است.');

            return;
        }

        showError('[data-timestamp-error]', '');
        result('jalali').textContent = formatJalali(date);
        result('gregorian').textContent = formatGregorian(date);
        result('iso').textContent = date.toISOString();
        result('relative').textContent = formatRelative(date);
        result('unit').textContent = unit;
        resultList.classList.remove('hidden');
    };

    /* ---------- Date → timestamp ---------- */

    const fillDateWith = (date) => {
        const parts = partsIn(date);
        const shown = calendar() === 'jalali' ? { ...parts, ...gregorianToJalali(parts.year, parts.month, parts.day) } : parts;

        for (const name of ['year', 'month', 'day', 'hour', 'minute', 'second']) {
            datePart(name).value = shown[name];
        }
    };

    const readDate = () => {
        const values = {};

        for (const name of ['year', 'month', 'day', 'hour', 'minute', 'second']) {
            const raw = toLatinDigits(datePart(name).value.trim());
            values[name] = raw === '' && ['hour', 'minute', 'second'].includes(name) ? 0 : Number(raw);

            if (raw !== '' && !/^\d+$/.test(raw)) {
                return { error: 'همه بخش‌ها باید عدد باشند.' };
            }
        }

        const { year, month, day, hour, minute, second } = values;

        if (!year || month < 1 || month > 12 || day < 1 || hour > 23 || minute > 59 || second > 59) {
            return { error: 'تاریخ یا ساعت معتبر نیست.' };
        }

        if (calendar() === 'jalali') {
            if (year < 1 || year > 3000 || day > jalaliMonthLength(year, month)) {
                return { error: 'این روز در تقویم شمسی وجود ندارد.' };
            }

            return { value: { ...values, ...jalaliToGregorian(year, month, day) } };
        }

        if (day > new Date(Date.UTC(year, month, 0)).getUTCDate()) {
            return { error: 'این روز در تقویم میلادی وجود ندارد.' };
        }

        return { value: values };
    };

    const convertDate = () => {
        const resultList = tool.querySelector('[data-date-result]');
        const isEmpty = ['year', 'month', 'day'].every((name) => datePart(name).value.trim() === '');

        if (isEmpty) {
            resultList.classList.add('hidden');
            showError('[data-date-error]', '');

            return;
        }

        const { value, error } = readDate();

        if (error) {
            resultList.classList.add('hidden');
            showError('[data-date-error]', error);

            return;
        }

        const date = zonedTimeToDate(value);
        showError('[data-date-error]', '');
        result('seconds').textContent = Math.floor(date.getTime() / 1000);
        result('milliseconds').textContent = date.getTime();
        result('counterpart').textContent = calendar() === 'jalali' ? formatGregorian(date) : formatJalali(date);
        result('counterpart').dir = calendar() === 'jalali' ? 'ltr' : 'rtl';
        resultList.classList.remove('hidden');
    };

    /* ---------- Events ---------- */

    timestampInput.addEventListener('input', convertTimestamp);
    tool.querySelectorAll('[data-date-part]').forEach((input) => input.addEventListener('input', convertDate));

    timezoneSelect.addEventListener('change', () => {
        tick();
        convertTimestamp();
        convertDate();
    });

    tool.querySelectorAll('[data-calendar]').forEach((radio) =>
        radio.addEventListener('change', () => {
            // Keep the same moment: read the fields in the old calendar, then show them in the new one.
            const { value } = readDate();
            currentCalendar = radio.value;
            fillDateWith(value ? zonedTimeToDate(value) : new Date());
            convertDate();
        }),
    );

    tool.addEventListener('click', (event) => {
        const action = event.target.closest('[data-action]')?.dataset.action;

        if (action === 'use-now') {
            timestampInput.value = Math.floor(Date.now() / 1000);
            convertTimestamp();
        } else if (action === 'date-now') {
            fillDateWith(new Date());
            convertDate();
        }
    });

    tick();
    const clock = setInterval(tick, 1000);
    fillDateWith(new Date());
    convertDate();

    return () => clearInterval(clock);
});
