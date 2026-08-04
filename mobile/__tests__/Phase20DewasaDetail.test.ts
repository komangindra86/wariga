import type { RecommendationReason } from '../src/types/domain';
import { groupReasons, readableValue } from '../src/utils/dewasaDetail';

const reason = (
    overrides: Partial<RecommendationReason>,
): RecommendationReason => ({
    matched: true,
    reason: 'Alasan pengujian',
    rule_code: 'RULE-1',
    rule_id: 1,
    rule_name: 'Rule pengujian',
    rule_type: 'positive',
    score_effect: 10,
    severity: 'info',
    ...overrides,
});

describe('Fase 20 detail rekomendasi dan mode ahli', () => {
    test('memisahkan alasan pendukung dan pertimbangan', () => {
        const grouped = groupReasons([
            reason({}),
            reason({
                rule_id: 2,
                rule_type: 'prohibition',
                score_effect: -20,
            }),
            reason({ matched: false, rule_id: 3 }),
        ]);

        expect(grouped.recommendations.map(item => item.rule_id)).toEqual([1]);
        expect(grouped.considerations.map(item => item.rule_id)).toEqual([2]);
    });

    test('menampilkan nilai rule ahli secara ramah pengguna', () => {
        expect(readableValue(['Sinta', 'Landep'])).toBe('Sinta, Landep');
        expect(readableValue(false)).toBe('Tidak');
        expect(readableValue(null)).toBe('Tidak tersedia');
    });
});
