import type { DewasaRecommendation } from '@/types/domain';

export type ResultFilter =
    | 'all'
    | 'recommended'
    | 'good'
    | 'consideration'
    | 'avoid';

export function matchesResultFilter(
    item: DewasaRecommendation,
    filter: ResultFilter,
) {
    if (filter === 'all') {
        return true;
    }
    if (filter === 'good') {
        return item.classification === 'Cukup Baik';
    }
    if (filter === 'recommended') {
        return (
            item.status === 'recommended' &&
            item.classification !== 'Cukup Baik'
        );
    }
    return item.status === filter;
}

export function statusPresentation(item: DewasaRecommendation) {
    if (item.status === 'avoid') {
        return { label: 'Hindari', tone: 'danger' as const };
    }
    if (item.status === 'consideration') {
        return { label: 'Pertimbangkan', tone: 'warning' as const };
    }
    if (item.classification === 'Cukup Baik') {
        return { label: 'Cukup baik', tone: 'info' as const };
    }
    return { label: 'Baik', tone: 'success' as const };
}
