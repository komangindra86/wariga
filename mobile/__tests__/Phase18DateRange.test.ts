import {
    makassarToday,
    presetDateRange,
    validateDateRange,
} from '../src/utils/dateRange';

describe('Fase 18 pemilihan rentang waktu', () => {
    test('menghitung hari ini dalam zona Asia/Makassar', () => {
        expect(makassarToday(new Date('2026-07-29T16:30:00.000Z'))).toBe(
            '2026-07-30',
        );
    });

    test('pilihan 7 hari bersifat inklusif', () => {
        expect(presetDateRange('next_7_days', '2026-07-30')).toEqual({
            startDate: '2026-07-30',
            endDate: '2026-08-05',
        });
    });

    test('menghitung bulan depan lintas tahun', () => {
        expect(presetDateRange('next_month', '2026-12-20')).toEqual({
            startDate: '2027-01-01',
            endDate: '2027-01-31',
        });
    });

    test('menolak urutan, format, dan rentang lebih dari 366 hari', () => {
        expect(
            validateDateRange({
                startDate: '2026-08-02',
                endDate: '2026-08-01',
            }),
        ).toContain('tidak boleh');
        expect(
            validateDateRange({
                startDate: '2026-02-30',
                endDate: '2026-03-01',
            }),
        ).toContain('format');
        expect(
            validateDateRange({
                startDate: '2026-01-01',
                endDate: '2027-01-02',
            }),
        ).toContain('366');
    });
});
