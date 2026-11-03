import { format, parseISO } from 'date-fns';
import { id } from 'date-fns/locale';

import type {
    DewasaRecommendation,
    SavedDewasa,
} from '@/types/domain';
import { statusPresentation } from '@/utils/dewasaResults';

export function savedRecommendation(
    saved: SavedDewasa,
): DewasaRecommendation | undefined {
    return saved.result.recommendations?.[0];
}

export function savedDateLabel(date: string) {
    return format(parseISO(date), 'EEEE, d MMMM yyyy', { locale: id });
}

export function savedStatusLabel(saved: SavedDewasa) {
    const recommendation = savedRecommendation(saved);
    return recommendation
        ? statusPresentation(recommendation).label
        : saved.status;
}

export function buildSavedShareMessage(saved: SavedDewasa, url: string) {
    const recommendation = savedRecommendation(saved);
    const calendar = recommendation?.calendar;
    const saptaWara =
        calendar?.wewaran?.sapta_wara?.nama ?? calendar?.wewaran?.saptawara?.nama ?? 'Tidak tersedia';
    const pancaWara =
        calendar?.wewaran?.panca_wara?.nama ?? calendar?.wewaran?.pancawara?.nama ?? 'Tidak tersedia';
    const wuku = calendar?.wuku?.nama ?? 'Tidak tersedia';

    return [
        'Hari Baik (Dewasa Ayu)',
        '',
        `Kegiatan: ${saved.activity?.name ?? 'Kegiatan'}`,
        `Tanggal: ${savedDateLabel(saved.date)}`,
        `Kalender Bali: ${saptaWara} ${pancaWara}`,
        `Wuku: ${wuku}`,
        `Status: ${savedStatusLabel(saved)}`,
        `Nilai: ${saved.score}/100`,
        '',
        'Lihat penjelasan:',
        url,
    ].join('\n');
}
