/**
 * Runs inside the Bizagi page (every frame). Must be self-contained: it is injected with
 * chrome.scripting.executeScript, so it cannot use anything defined outside this function.
 *
 * The grid is found by its header labels (تاریخ، ساعت ورود ۱، ساعت خروج ۱، ...), not by ids,
 * and every row is identified by the Jalali date written in it, because the visible range
 * of days in Bizagi is not fixed.
 *
 * @returns {{days: Array<{date: string, pairs: Array<{arrive: ?string, leave: ?string}>, holiday_label: ?string}>}}
 */
export function extractBizagiAttendance() {
    const toLatinDigits = (value) =>
        value
            .replace(/[۰-۹]/g, (digit) => '۰۱۲۳۴۵۶۷۸۹'.indexOf(digit))
            .replace(/[٠-٩]/g, (digit) => '٠١٢٣٤٥٦٧٨٩'.indexOf(digit));

    const clean = (value) => toLatinDigits((value ?? '').replace(/\s+/g, ' ').trim());

    const cellValue = (cell) => {
        const input = cell?.querySelector('input, textarea');

        return clean(input ? input.value : cell?.textContent);
    };

    const isTime = (value) => /^\d{1,2}:\d{2}$/.test(value);
    const asTime = (value) => (isTime(value) && !/^0?0:00$/.test(value) ? value.padStart(5, '0') : null);

    for (const table of document.querySelectorAll('table')) {
        const headerCells = [...table.querySelectorAll('thead tr, tr')]
            .map((row) => [...row.children])
            .find((cells) => cells.some((cell) => /ساعت\s*ورود/.test(cell.textContent)));

        if (!headerCells) {
            continue;
        }

        const columns = { arrive: {}, leave: {} };

        headerCells.forEach((cell, index) => {
            const label = clean(cell.textContent);
            let match;

            if (label === 'تاریخ') {
                columns.date = index;
            } else if ((match = label.match(/^ساعت ورود\s*(\d)$/))) {
                columns.arrive[match[1]] = index;
            } else if ((match = label.match(/^ساعت خروج\s*(\d)$/))) {
                columns.leave[match[1]] = index;
            }
        });

        if (columns.date === undefined || columns.arrive[1] === undefined) {
            continue;
        }

        const pairNumbers = Object.keys(columns.arrive).sort();
        const days = [];

        for (const row of table.querySelectorAll('tbody tr')) {
            const cells = [...row.children];
            const dateMatch = cellValue(cells[columns.date]).match(/(\d{4})\/(\d{1,2})\/(\d{1,2})/);

            // Footer rows ("جمع کل کارکرد ماه جاری", ...) have no date.
            if (!dateMatch) {
                continue;
            }

            let holidayLabel = null;

            const pairs = pairNumbers.map((pair) => {
                const arrive = cellValue(cells[columns.arrive[pair]]);
                const leave = cellValue(cells[columns.leave[pair]]);

                // Cells like "تعطیلی جمعه" are not times but tell us the day is off.
                for (const value of [arrive, leave]) {
                    if (value && !isTime(value) && holidayLabel === null) {
                        holidayLabel = value;
                    }
                }

                return { arrive: asTime(arrive), leave: asTime(leave) };
            });

            days.push({ date: `${dateMatch[1]}/${dateMatch[2]}/${dateMatch[3]}`, pairs, holiday_label: holidayLabel });
        }

        if (days.length > 0) {
            return { days };
        }
    }

    return { days: [] };
}
