/**
 * Jalali (Persian) ⇄ Gregorian date conversion, based on the well-known "jalaali-js" algorithm
 * (Borkowski's leap-year breaks). Valid for Jalali years -61 … 3177.
 * Displaying dates in Jalali is done with Intl ("fa-IR-u-ca-persian"); this is only needed to
 * turn a Jalali date typed by the user into a Gregorian one.
 */
const breaks = [-61, 9, 38, 199, 426, 686, 756, 818, 1111, 1181, 1210, 1635, 2060, 2097, 2192, 2262, 2324, 2394, 2456, 3178];

const div = (a, b) => Math.trunc(a / b);
const mod = (a, b) => a - Math.trunc(a / b) * b;

const jalaliCalendar = (jalaliYear) => {
    const gregorianYear = jalaliYear + 621;
    let leapJalali = -14;
    let previousBreak = breaks[0];
    let jump = 0;

    if (jalaliYear < previousBreak || jalaliYear >= breaks[breaks.length - 1]) {
        throw new RangeError(`Invalid Jalali year ${jalaliYear}`);
    }

    for (let index = 1; index < breaks.length; index++) {
        const nextBreak = breaks[index];
        jump = nextBreak - previousBreak;

        if (jalaliYear < nextBreak) {
            break;
        }

        leapJalali += div(jump, 33) * 8 + div(mod(jump, 33), 4);
        previousBreak = nextBreak;
    }

    let yearsSinceBreak = jalaliYear - previousBreak;
    leapJalali += div(yearsSinceBreak, 33) * 8 + div(mod(yearsSinceBreak, 33) + 3, 4);

    if (mod(jump, 33) === 4 && jump - yearsSinceBreak === 4) {
        leapJalali += 1;
    }

    const leapGregorian = div(gregorianYear, 4) - div((div(gregorianYear, 100) + 1) * 3, 4) - 150;
    const march = 20 + leapJalali - leapGregorian;

    if (jump - yearsSinceBreak < 6) {
        yearsSinceBreak = yearsSinceBreak - jump + div(jump + 4, 33) * 33;
    }

    let leap = mod(mod(yearsSinceBreak + 1, 33) - 1, 4);

    if (leap === -1) {
        leap = 4;
    }

    return { leap, gregorianYear, march };
};

const gregorianToDayNumber = (year, month, day) =>
    div((year + div(month - 8, 6) + 100100) * 1461, 4) +
    div(153 * mod(month + 9, 12) + 2, 5) +
    day -
    34840408 -
    div(div(year + 100100 + div(month - 8, 6), 100) * 3, 4) +
    752;

const dayNumberToGregorian = (dayNumber) => {
    let j = 4 * dayNumber + 139361631;
    j += div(div(4 * dayNumber + 183187720, 146097) * 3, 4) * 4 - 3908;
    const i = div(mod(j, 1461), 4) * 5 + 308;
    const day = div(mod(i, 153), 5) + 1;
    const month = mod(div(i, 153), 12) + 1;
    const year = div(j, 1461) - 100100 + div(8 - month, 6);

    return { year, month, day };
};

export const isJalaliLeapYear = (year) => jalaliCalendar(year).leap === 0;

export const jalaliMonthLength = (year, month) => {
    if (month <= 6) {
        return 31;
    }

    if (month <= 11) {
        return 30;
    }

    return isJalaliLeapYear(year) ? 30 : 29;
};

/**
 * @returns {{year: number, month: number, day: number}} The Gregorian date (month 1–12).
 */
export const jalaliToGregorian = (year, month, day) => {
    const { gregorianYear, march } = jalaliCalendar(year);
    const dayNumber = gregorianToDayNumber(gregorianYear, 3, march) + (month - 1) * 31 - div(month, 7) * (month - 7) + day - 1;

    return dayNumberToGregorian(dayNumber);
};

/**
 * @returns {{year: number, month: number, day: number}} The Jalali date (month 1–12).
 */
export const gregorianToJalali = (year, month, day) => {
    const dayNumber = gregorianToDayNumber(year, month, day);
    let jalaliYear = dayNumberToGregorian(dayNumber).year - 621;
    const calendar = jalaliCalendar(jalaliYear);
    let daysSinceNowruz = dayNumber - gregorianToDayNumber(calendar.gregorianYear, 3, calendar.march);

    if (daysSinceNowruz >= 0) {
        if (daysSinceNowruz <= 185) {
            return { year: jalaliYear, month: 1 + div(daysSinceNowruz, 31), day: mod(daysSinceNowruz, 31) + 1 };
        }

        daysSinceNowruz -= 186;
    } else {
        jalaliYear -= 1;
        daysSinceNowruz += 179;

        if (calendar.leap === 1) {
            daysSinceNowruz += 1;
        }
    }

    return { year: jalaliYear, month: 7 + div(daysSinceNowruz, 30), day: mod(daysSinceNowruz, 30) + 1 };
};
