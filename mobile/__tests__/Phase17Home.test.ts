import { mapCalendarDate } from '../src/api/endpoints';

describe('Fase 17 beranda', () => {
    test('memetakan respons kalender hari ini dari API', () => {
        expect(
            mapCalendarDate({
                calendar: {
                    tanggal_masehi: '2026-07-30',
                    wuku: { nama: 'Sinta' },
                    wewaran: {
                        pancawara: { nama: 'Umanis' },
                        saptawara: { nama: 'Wraspati' },
                    },
                },
                lunar: {
                    available: true,
                    fase: 'penanggal',
                    nomor_hari: 15,
                    is_purnama: true,
                    sasih: { nama: 'Kasa' },
                    verification_status: 'verified',
                },
            }),
        ).toMatchObject({
            date: '2026-07-30',
            is_full_moon: true,
            pancawara: 'Umanis',
            penanggal: 15,
            saptawara: 'Wraspati',
            sasih: 'Kasa',
            verification_status: 'verified',
            wuku: 'Sinta',
        });
    });

    test('menandai sasih nampih dengan awalan Mala', () => {
        expect(
            mapCalendarDate({
                calendar: { tanggal_masehi: '2027-05-20', wewaran: {} },
                lunar: {
                    available: true,
                    fase: 'penanggal',
                    nomor_hari: 15,
                    is_purnama: true,
                    is_nampih_sasih: true,
                    sasih: { nama: 'Jiyestha' },
                },
            }).sasih,
        ).toBe('Mala Jiyestha');
    });

    test('jujur ketika data lunar tidak tersedia', () => {
        expect(
            mapCalendarDate({
                calendar: {
                    tanggal_masehi: '2026-07-30',
                    wewaran: {},
                },
                lunar: {
                    available: false,
                    message: 'Data sasih dan fase bulan belum tersedia.',
                    verification_status: 'unavailable',
                },
            }),
        ).toMatchObject({
            lunar_available: false,
            lunar_message: 'Data sasih dan fase bulan belum tersedia.',
            verification_status: 'unavailable',
        });
    });
});
