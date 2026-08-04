import { unwrapApiData } from '../src/api/endpoints';
import { normalizeApiError } from '../src/api/interceptors';
import { queryKeys } from '../src/api/queryKeys';
import { useAppStore } from '../src/store/appStore';

describe('Phase 16 API client and state management', () => {
    test.each([
        [401, 'UNAUTHENTICATED'],
        [403, 'FORBIDDEN'],
        [404, 'NOT_FOUND'],
        [422, 'VALIDATION'],
        [429, 'RATE_LIMITED'],
        [500, 'SERVER_ERROR'],
        [503, 'MAINTENANCE'],
    ] as const)(
        'normalizes HTTP %i without exposing server details',
        (status, code) => {
            const error = normalizeApiError({
                isAxiosError: true,
                response: {
                    status,
                    data: {
                        message: 'SQLSTATE rahasia',
                        errors: { email: ['Email tidak valid.'] },
                    },
                    headers: {},
                },
            });

            expect(error.code).toBe(code);
            expect(error.message).not.toContain('SQLSTATE');
        },
    );

    test('creates stable and scoped query keys', () => {
        expect(queryKeys.calendar.date('2026-07-30')).toEqual([
            'calendar',
            'rich-v3',
            'date',
            '2026-07-30',
        ]);
        expect(queryKeys.dewasa.detail('pernikahan', '2026-08-01')).toEqual([
            'dewasa',
            'detail',
            'pernikahan',
            '2026-08-01',
        ]);
    });

    test('unwraps API envelopes both before and after transport interception', () => {
        const payload = { items: [{ id: 1 }] };
        const envelope = {
            success: true as const,
            message: 'OK',
            data: payload,
        };

        expect(unwrapApiData({ data: envelope })).toEqual(payload);
        expect(unwrapApiData(envelope)).toEqual(payload);
        expect(unwrapApiData({ data: payload })).toEqual(payload);
    });

    test('keeps temporary search state and can reset it', () => {
        const store = useAppStore.getState();
        store.setSelectedActivity('pernikahan');
        store.setDateRange('2026-08-01', '2026-08-31');
        store.setSearchMode('expert');
        store.setResultFilters({ minimumScore: 70 });

        expect(useAppStore.getState().selectedActivity).toBe('pernikahan');
        useAppStore.getState().resetSearch();

        expect(useAppStore.getState()).toMatchObject({
            selectedActivity: null,
            dateRange: { startDate: null, endDate: null },
            searchMode: 'simple',
            resultFilters: { classification: 'all', minimumScore: null },
        });
    });
});
