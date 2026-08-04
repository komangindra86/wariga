import type { DewasaSearchParams } from '@/types/domain';

export const queryKeys = {
    activities: {
        all: ['activities'] as const,
    },
    calendar: {
        all: ['calendar', 'rich-v3'] as const,
        today: () => [...queryKeys.calendar.all, 'today'] as const,
        date: (date: string) =>
            [...queryKeys.calendar.all, 'date', date] as const,
        month: (year: number, month: number) =>
            [...queryKeys.calendar.all, 'month', year, month] as const,
    },
    dewasa: {
        all: ['dewasa'] as const,
        today: () => [...queryKeys.dewasa.all, 'today'] as const,
        search: (params: DewasaSearchParams) =>
            [...queryKeys.dewasa.all, 'search', params] as const,
        detail: (activity: string, date: string) =>
            [...queryKeys.dewasa.all, 'detail', activity, date] as const,
    },
    saved: {
        all: ['saved-dewasa'] as const,
        detail: (id: number) => [...queryKeys.saved.all, 'detail', id] as const,
    },
    user: ['user'] as const,
} as const;
