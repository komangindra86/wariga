import {
    addMonths,
    eachDayOfInterval,
    endOfMonth,
    format,
    getDay,
    isSameDay,
    parseISO,
    startOfMonth,
} from 'date-fns';

import type { BaliCalendarDate } from '@/types/domain';

export const WEEKDAY_LABELS = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];

export type CalendarCell = {
    date: Date;
    item?: BaliCalendarDate;
    key: string;
    outsideMonth: boolean;
};

export function buildMonthGrid(
    month: Date,
    items: BaliCalendarDate[],
): Array<CalendarCell | null> {
    const first = startOfMonth(month);
    const byDate = new Map(items.map(item => [item.date, item]));
    const leading = Array<null>(getDay(first)).fill(null);
    const days = eachDayOfInterval({ start: first, end: endOfMonth(month) });

    return [
        ...leading,
        ...days.map(date => {
            const key = format(date, 'yyyy-MM-dd');
            return {
                date,
                item: byDate.get(key),
                key,
                outsideMonth: false,
            };
        }),
    ];
}

export function moveMonth(month: Date, offset: number): Date {
    return startOfMonth(addMonths(month, offset));
}

export function isToday(date: Date, today = new Date()): boolean {
    return isSameDay(date, today);
}

export function importantDay(item?: BaliCalendarDate): string | null {
    if (item?.is_full_moon) return 'Purnama';
    if (item?.is_new_moon) return 'Tilem';
    return item?.observances?.[0]?.name ?? item?.ceremonies?.[0] ?? null;
}

export type CalendarMarker = 'purnama' | 'tilem' | 'rahina' | null;

export function calendarMarker(item?: BaliCalendarDate): CalendarMarker {
    if (item?.is_full_moon) return 'purnama';
    if (item?.is_new_moon) return 'tilem';
    if (
        item?.observances?.some(
            observance => observance.type === 'balinese_observance',
        )
    ) {
        return 'rahina';
    }

    return null;
}

export function parseCalendarDate(date: string): Date {
    return parseISO(date);
}

/** Nama sasih untuk tampilan; sasih nampih ditandai "Mala", mis. "Mala Jiyestha". */
export function sasihLabel(
    lunar?: {
        sasih?: { nama?: string | null } | null;
        is_nampih_sasih?: boolean;
    } | null,
): string | null {
    const name = lunar?.sasih?.nama;
    if (!name) return null;
    return lunar?.is_nampih_sasih ? `Mala ${name}` : name;
}

/** Fase bulan untuk tampilan: "Purnama", "Tilem", "Penanggal 3", atau "Pangelong 6". */
export function lunarPhaseLabel(
    item?: {
        is_full_moon?: boolean;
        is_new_moon?: boolean;
        penanggal?: number | null;
        pangelong?: number | null;
    } | null,
): string | null {
    if (item?.is_full_moon) return 'Purnama';
    if (item?.is_new_moon) return 'Tilem';
    if (item?.penanggal) return `Penanggal ${item.penanggal}`;
    if (item?.pangelong) return `Pangelong ${item.pangelong}`;
    return null;
}
