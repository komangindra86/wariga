import type { RecommendationReason } from '@/types/domain';

export function readableValue(value: unknown): string {
    if (value === null || value === undefined || value === '') {
        return 'Tidak tersedia';
    }
    if (typeof value === 'boolean') {
        return value ? 'Ya' : 'Tidak';
    }
    if (Array.isArray(value)) {
        return value.map(readableValue).join(', ');
    }
    if (typeof value === 'object') {
        return JSON.stringify(value);
    }
    return String(value);
}

export function groupReasons(reasons: RecommendationReason[]) {
    return {
        considerations: reasons.filter(
            reason =>
                reason.matched &&
                (reason.score_effect < 0 ||
                    reason.rule_type === 'negative' ||
                    reason.rule_type === 'prohibition'),
        ),
        recommendations: reasons.filter(
            reason =>
                reason.matched &&
                reason.score_effect >= 0 &&
                reason.rule_type !== 'negative' &&
                reason.rule_type !== 'prohibition',
        ),
    };
}
