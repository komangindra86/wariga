import { format } from 'date-fns';

import type { BaliCalendarDate } from '../src/types/domain';
import {
    buildMonthGrid,
    calendarMarker,
    importantDay,
    moveMonth,
} from '../src/utils/calendar';

const day = (
    date: string,
    overrides: Partial<BaliCalendarDate> = {},
): BaliCalendarDate => ({
    date,
    lunar_available: true,
    pancawara: 'Umanis',
    verification_status: 'verified',
    ...overrides,
});

describe('Fase 21 kalender Bali bulanan', () => {
    test('membentuk grid bulan dengan posisi hari yang tepat', () => {
        const grid = buildMonthGrid(new Date(2026, 7, 1), [
            day('2026-08-01'),
            day('2026-08-31'),
        ]);

        expect(grid.slice(0, 6)).toEqual(Array(6).fill(null));
        expect(grid[6]?.key).toBe('2026-08-01');
        expect(grid.at(-1)?.key).toBe('2026-08-31');
    });

    test('perpindahan bulan aman melewati batas tahun', () => {
        expect(format(moveMonth(new Date(2026, 11, 1), 1), 'yyyy-MM')).toBe(
            '2027-01',
        );
    });

    test('menandai Purnama, Tilem, dan rahina penting', () => {
        expect(importantDay(day('2026-08-01', { is_full_moon: true }))).toBe(
            'Purnama',
        );
        expect(importantDay(day('2026-08-02', { is_new_moon: true }))).toBe(
            'Tilem',
        );
        expect(
            importantDay(
                day('2026-08-03', {
                    observances: [
                        {
                            name: 'Kajeng Kliwon',
                            type: 'balinese_observance',
                        },
                    ],
                }),
            ),
        ).toBe('Kajeng Kliwon');
    });

    test('tanggal merah tidak mendapat bulatan rahina', () => {
        expect(
            calendarMarker(
                day('2026-08-17', {
                    is_red_date: true,
                    observances: [
                        {
                            name: 'Proklamasi Kemerdekaan',
                            type: 'national_holiday',
                        },
                    ],
                }),
            ),
        ).toBeNull();
        expect(
            calendarMarker(
                day('2026-06-17', {
                    is_red_date: true,
                    observances: [
                        {
                            name: 'Hari Raya Galungan',
                            type: 'hindu_holiday',
                        },
                    ],
                }),
            ),
        ).toBeNull();
        expect(
            calendarMarker(
                day('2026-08-06', {
                    observances: [
                        {
                            name: 'Kajeng Kliwon',
                            type: 'balinese_observance',
                        },
                    ],
                }),
            ),
        ).toBe('rahina');
    });
});
