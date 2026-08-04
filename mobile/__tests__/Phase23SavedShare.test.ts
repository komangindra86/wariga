import type { SavedDewasa } from '@/types/domain';
import {
    buildSavedShareMessage,
    savedRecommendation,
    savedStatusLabel,
} from '@/utils/savedDewasa';

const saved: SavedDewasa = {
    activity: { id: 4, name: 'Membangun Rumah', slug: 'membangun-rumah' },
    created_at: '2026-07-30T02:00:00.000Z',
    dataset_version: '23.0.0',
    date: '2026-08-17',
    engine_version: '23.0.0',
    id: 9,
    notes: 'Pilihan keluarga',
    result: {
        activity: {
            id: 4,
            name: 'Membangun Rumah',
            slug: 'membangun-rumah',
        },
        recommendations: [
            {
                calendar: {
                    tanggal_masehi: '2026-08-17',
                    wewaran: {
                        pancawara: { nama: 'Umanis' },
                        saptawara: { nama: 'Soma' },
                    },
                    wuku: { nama: 'Sinta' },
                },
                classification: 'Sangat Baik',
                date: '2026-08-17',
                main_reasons: [],
                score: 88,
                status: 'recommended',
            },
        ],
        summary: {
            avoid_count: 0,
            consideration_count: 0,
            recommended_count: 1,
            total_dates_checked: 1,
        },
    },
    rule_version: '23.0.0',
    score: 88,
    snapshot_notice: null,
    snapshot_outdated: false,
    status: 'recommended',
};

describe('Phase 23 saved results and sharing', () => {
    it('reads the recommendation from the immutable saved snapshot', () => {
        expect(savedRecommendation(saved)?.score).toBe(88);
        expect(savedStatusLabel(saved)).toBe('Rekomendasi');
    });

    it('builds the prescribed native share-sheet message', () => {
        const message = buildSavedShareMessage(
            saved,
            'https://wariga.test/api/v1/shared/token',
        );

        expect(message).toContain('Rekomendasi Dewasa Ayu');
        expect(message).toContain('Kegiatan: Membangun Rumah');
        expect(message).toContain('Kalender Bali: Soma Umanis');
        expect(message).toContain('Wuku: Sinta');
        expect(message).toContain('Status: Rekomendasi');
        expect(message).toContain('Skor: 88');
        expect(message).toContain(
            'Lihat penjelasan:\nhttps://wariga.test/api/v1/shared/token',
        );
    });
});
