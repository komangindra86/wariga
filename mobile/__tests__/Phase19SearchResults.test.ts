import type { DewasaRecommendation } from '../src/types/domain';
import {
    matchesResultFilter,
    statusPresentation,
} from '../src/utils/dewasaResults';

const result = (
    classification: string,
    status: DewasaRecommendation['status'],
): DewasaRecommendation => ({
    classification,
    date: '2026-07-30',
    main_reasons: [],
    score: 70,
    status,
});

describe('Fase 19 hasil pencarian Dewasa Ayu', () => {
    test('memisahkan rekomendasi utama dari cukup baik', () => {
        expect(
            matchesResultFilter(
                result('Direkomendasikan', 'recommended'),
                'recommended',
            ),
        ).toBe(true);
        expect(
            matchesResultFilter(
                result('Cukup Baik', 'recommended'),
                'recommended',
            ),
        ).toBe(false);
        expect(
            matchesResultFilter(result('Cukup Baik', 'recommended'), 'good'),
        ).toBe(true);
    });

    test('memetakan status API ke label yang ramah pengguna', () => {
        expect(
            statusPresentation(result('Perlu Pertimbangan', 'consideration')),
        ).toEqual({ label: 'Pertimbangan', tone: 'warning' });
        expect(
            statusPresentation(result('Sebaiknya Dihindari', 'avoid')),
        ).toEqual({ label: 'Hindari', tone: 'danger' });
    });
});
