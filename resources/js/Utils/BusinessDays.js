import {
    addDays,
    format,
    getYear,
    isSunday,
} from "date-fns";

const holidayCache = new Map();

function toDateKey(date) {
    return format(date, "yyyy-MM-dd");
}

function getEasterSunday(year) {
    const a = year % 19;
    const b = Math.floor(year / 100);
    const c = year % 100;
    const d = Math.floor(b / 4);
    const e = b % 4;
    const f = Math.floor((b + 8) / 25);
    const g = Math.floor((b - f + 1) / 3);
    const h = (19 * a + b - d - g + 15) % 30;
    const i = Math.floor(c / 4);
    const k = c % 4;
    const l = (32 + 2 * e + 2 * i - h - k) % 7;
    const m = Math.floor((a + 11 * h + 22 * l) / 451);
    const month = Math.floor((h + l - 7 * m + 114) / 31);
    const day = ((h + l - 7 * m + 114) % 31) + 1;

    return new Date(year, month - 1, day);
}

function getBrazilianHolidayKeys(year) {
    const keys = new Set();

    const add = (date) => keys.add(toDateKey(date));

    add(new Date(year, 0, 1));
    add(new Date(year, 3, 21));
    add(new Date(year, 4, 1));
    add(new Date(year, 8, 7));
    add(new Date(year, 9, 12));
    add(new Date(year, 10, 2));
    add(new Date(year, 10, 15));
    add(new Date(year, 11, 25));

    const easter = getEasterSunday(year);

    add(addDays(easter, -48));
    add(addDays(easter, -47));
    add(addDays(easter, -2));
    add(addDays(easter, 60));

    return keys;
}

export function isBrazilianHoliday(date) {
    const year = getYear(date);

    if( !holidayCache.has(year) ) {
        holidayCache.set(year, getBrazilianHolidayKeys(year));
    }

    return holidayCache.get(year).has(toDateKey(date));
}

export function isNonBusinessDay(date) {
    return isSunday(date) || isBrazilianHoliday(date);
}

export function adjustInstallmentDueDate(date) {
    let adjusted = new Date(date);

    for( let i = 0; i < 366 && isNonBusinessDay(adjusted); i++ ) {
        adjusted = addDays(adjusted, 1);
    }

    return adjusted;
}
