import { onPageLoad } from './support/page';

/**
 * Year/month picker of the payroll page: a calendar button that opens a panel with the 12 Jalali
 * months of a year. The viewed month is filled, the current pay month has a ring, and months with
 * recorded days or saved settings get a dot. Choosing a month opens its page.
 */
const toPersianDigits = (value) => String(value).replace(/\d/g, (digit) => '۰۱۲۳۴۵۶۷۸۹'[digit]);

const initPicker = (picker, signal) => {
    const toggle = picker.querySelector('[data-month-picker-toggle]');
    const panel = picker.querySelector('[data-month-picker-panel]');
    const monthsGrid = picker.querySelector('[data-picker-months]');
    const monthNames = JSON.parse(picker.dataset.monthNames);
    const monthsWithData = new Set(JSON.parse(picker.dataset.monthsWithData));
    const [selectedYear, selectedMonth] = picker.dataset.selected.split('-').map(Number);
    const [currentYear, currentMonth] = picker.dataset.current.split('-').map(Number);
    let shownYear = selectedYear;

    const render = () => {
        picker.querySelector('[data-picker-year]').textContent = toPersianDigits(shownYear);

        monthsGrid.replaceChildren(
            ...monthNames.map((name, index) => {
                const month = index + 1;
                const isSelected = shownYear === selectedYear && month === selectedMonth;
                const isCurrent = shownYear === currentYear && month === currentMonth;
                const hasData = monthsWithData.has(`${shownYear}-${month}`);

                const link = document.createElement('a');
                link.href = `${picker.dataset.baseUrl}/${shownYear}/${month}`;
                link.className = [
                    'relative flex flex-col items-center rounded-xl px-1 pt-2 pb-2.5 text-sm transition',
                    isSelected ? 'bg-sky-600 font-bold text-white shadow-sm' : 'text-slate-700 dark:text-slate-200 hover:bg-sky-50 dark:hover:bg-sky-950/50 hover:text-sky-800 dark:hover:text-sky-200',
                    isCurrent && !isSelected ? 'ring-2 ring-sky-300 dark:ring-sky-700 ring-inset' : '',
                ].join(' ');

                if (isSelected) {
                    link.setAttribute('aria-current', 'page');
                }

                const label = document.createElement('span');
                label.textContent = name;

                const dot = document.createElement('span');
                dot.className = `absolute bottom-1 size-1.5 rounded-full ${hasData ? (isSelected ? 'bg-white' : 'bg-emerald-500') : 'bg-transparent'}`;

                link.append(label, dot);
                link.title = `${name} ${toPersianDigits(shownYear)}${hasData ? ' — دارای داده' : ''}`;

                return link;
            }),
        );
    };

    const setOpen = (isOpen) => {
        panel.toggleAttribute('data-open', isOpen);
        toggle.setAttribute('aria-expanded', String(isOpen));

        if (isOpen) {
            shownYear = selectedYear;
            render();
            monthsGrid.querySelector('[aria-current]')?.focus();
        }
    };

    toggle.addEventListener('click', () => setOpen(!panel.hasAttribute('data-open')));

    picker.querySelectorAll('[data-year-step]').forEach((button) =>
        button.addEventListener('click', () => {
            shownYear = Math.min(1499, Math.max(1300, shownYear + Number(button.dataset.yearStep)));
            render();
        }),
    );

    document.addEventListener(
        'click',
        (event) => {
            if (panel.hasAttribute('data-open') && !picker.contains(event.target)) {
                setOpen(false);
            }
        },
        { signal },
    );

    document.addEventListener(
        'keydown',
        (event) => {
            if (event.key === 'Escape' && panel.hasAttribute('data-open')) {
                setOpen(false);
                toggle.focus();
            }
        },
        { signal },
    );
};

onPageLoad(() => {
    // Document listeners are removed when leaving the page, so they do not pile up.
    const documentListeners = new AbortController();
    document.querySelectorAll('[data-month-picker]').forEach((picker) => initPicker(picker, documentListeners.signal));

    return () => documentListeners.abort();
});
