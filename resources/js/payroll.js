/**
 * Live "total" per day in the attendance table while times are being typed.
 * The server recalculates everything on save; this is only a preview.
 */
const persianDigits = '۰۱۲۳۴۵۶۷۸۹';

const toLatinDigits = (value) =>
    value.replace(/[۰-۹]/g, (digit) => persianDigits.indexOf(digit)).replace(/[٠-٩]/g, (digit) => '٠١٢٣٤٥٦٧٨٩'.indexOf(digit));

const toMinutes = (value) => {
    const match = toLatinDigits(value.trim()).match(/^(\d{1,2})[:.]?(\d{2})$/);

    return match ? Number(match[1]) * 60 + Number(match[2]) : null;
};

const formatMinutes = (minutes) =>
    `${Math.floor(minutes / 60)}:${String(minutes % 60).padStart(2, '0')}`.replace(/\d/g, (digit) => persianDigits[digit]);

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

document.addEventListener('input', (event) => {
    const row = event.target.closest('[data-attendance-row]');

    if (row && event.target.matches('[data-time-input]')) {
        refreshRowTotal(row);
    }
});
