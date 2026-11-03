import {
    addDays,
    addMonths,
    differenceInCalendarDays,
    endOfMonth,
    format,
    isValid,
    parseISO,
    startOfMonth,
} from 'date-fns';

export const MAKASSAR_TIME_ZONE = 'Asia/Makassar';
export const MAX_RANGE_DAYS = 366;

export type DateRangePreset =
    | 'next_7_days'
    | 'next_30_days'
    | 'this_month'
    | 'next_month'
    | 'custom';

export type DateRange = {
    startDate: string;
    endDate: string;
};

export function makassarToday(now = new Date()): string {
    const parts = new Intl.DateTimeFormat('en-CA', {
        day: '2-digit',
        month: '2-digit',
        timeZone: MAKASSAR_TIME_ZONE,
        year: 'numeric',
    }).formatToParts(now);
    const value = (type: Intl.DateTimeFormatPartTypes) =>
        parts.find(part => part.type === type)?.value ?? '';

    return `${value('year')}-${value('month')}-${value('day')}`;
}

export function presetDateRange(
    preset: Exclude<DateRangePreset, 'custom'>,
    today = makassarToday(),
): DateRange {
    const date = parseISO(today);

    if (preset === 'next_7_days') {
        return {
            startDate: today,
            endDate: format(addDays(date, 6), 'yyyy-MM-dd'),
        };
    }
    if (preset === 'next_30_days') {
        return {
            startDate: today,
            endDate: format(addDays(date, 29), 'yyyy-MM-dd'),
        };
    }
    if (preset === 'this_month') {
        return {
            // Mulai hari ini: tanggal yang sudah lewat tidak berguna untuk dicari.
            startDate: today,
            endDate: format(endOfMonth(date), 'yyyy-MM-dd'),
        };
    }

    const nextMonth = addMonths(date, 1);
    return {
        startDate: format(startOfMonth(nextMonth), 'yyyy-MM-dd'),
        endDate: format(endOfMonth(nextMonth), 'yyyy-MM-dd'),
    };
}

export function validateDateRange(range: DateRange): string | null {
    const start = parseISO(range.startDate);
    const end = parseISO(range.endDate);
    const datePattern = /^\d{4}-\d{2}-\d{2}$/;

    if (
        !datePattern.test(range.startDate) ||
        !datePattern.test(range.endDate) ||
        !isValid(start) ||
        !isValid(end) ||
        format(start, 'yyyy-MM-dd') !== range.startDate ||
        format(end, 'yyyy-MM-dd') !== range.endDate
    ) {
        return 'Gunakan format tanggal YYYY-MM-DD yang valid.';
    }

    const difference = differenceInCalendarDays(end, start);
    if (difference < 0)
        return 'Tanggal awal tidak boleh setelah tanggal akhir.';
    if (difference + 1 > MAX_RANGE_DAYS) {
        return `Rentang tanggal maksimal ${MAX_RANGE_DAYS} hari.`;
    }

    return null;
}
