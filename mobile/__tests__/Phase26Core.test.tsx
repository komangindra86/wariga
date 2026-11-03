import React from 'react';
import { Text } from 'react-native';
import { SafeAreaProvider } from 'react-native-safe-area-context';
import ReactTestRenderer, { act } from 'react-test-renderer';

const mockQueryState = {
    data: undefined,
    error: undefined,
    fetchStatus: 'idle',
    isError: false,
    isPending: true,
    isRefetching: false,
    refetch: jest.fn(async () => undefined),
};

jest.mock('@tanstack/react-query', () => {
    const actual = jest.requireActual('@tanstack/react-query');
    return {
        ...actual,
        useQuery: jest.fn(() => mockQueryState),
        useQueryClient: jest.fn(() => ({
            getQueryData: jest.fn(),
            invalidateQueries: jest.fn(async () => undefined),
            prefetchQuery: jest.fn(async () => undefined),
        })),
    };
});

import { createApiError, normalizeApiError } from '@/api/interceptors';
import { queryKeys } from '@/api/queryKeys';
import {
    AppAccordion,
    AppBadge,
    AppButton,
    AppCard,
    AppEmptyState,
    AppErrorState,
    AppSkeleton,
    AppText,
} from '@/components';
import { ActivityListScreen } from '@/screens/ActivityListScreen';
import { LoginScreen } from '@/screens/AuthScreens';
import { CalendarScreen } from '@/screens/CalendarScreen';
import { DateRangeScreen } from '@/screens/DateRangeScreen';
import { DewasaDetailScreen } from '@/screens/DewasaDetailScreen';
import { HomeScreen } from '@/screens/HomeScreen';
import { SearchResultScreen } from '@/screens/SearchResultScreen';
import { SavedScreen } from '@/screens/TabScreens';
import { useAppStore } from '@/store/appStore';
import { useAuthStore } from '@/store/authStore';
import type { DewasaRecommendation } from '@/types/domain';
import {
    makassarToday,
    presetDateRange,
    validateDateRange,
} from '@/utils/dateRange';
import { statusPresentation } from '@/utils/dewasaResults';
import { nonEmptyTextSchema } from '@/validation/common';

const navigation = {
    goBack: jest.fn(),
    navigate: jest.fn(),
    replace: jest.fn(),
} as any;

function textContent(renderer: ReactTestRenderer.ReactTestRenderer) {
    return renderer.root
        .findAllByType(Text)
        .map(node => node.props.children)
        .flat(Infinity)
        .filter(value => typeof value === 'string')
        .join(' ');
}

function firstButton(renderer: ReactTestRenderer.ReactTestRenderer) {
    return renderer.root.find(
        node =>
            node.props.accessibilityRole === 'button' &&
            typeof node.props.onPress === 'function',
    );
}

describe('Fase 26 unit tests', () => {
    afterEach(() => {
        useAppStore.getState().resetSearch();
        useAuthStore.setState({
            isAuthenticated: false,
            isRestoring: false,
            user: null,
        });
    });

    test('formatter tanggal memakai kalender Asia/Makassar', () => {
        expect(makassarToday(new Date('2026-07-29T16:30:00.000Z'))).toBe(
            '2026-07-30',
        );
        expect(presetDateRange('next_7_days', '2026-07-30')).toEqual({
            startDate: '2026-07-30',
            endDate: '2026-08-05',
        });
    });

    test('validator menolak teks kosong dan rentang tanggal tidak valid', () => {
        expect(nonEmptyTextSchema.safeParse('  ').success).toBe(false);
        expect(
            validateDateRange({
                startDate: '2026-08-02',
                endDate: '2026-08-01',
            }),
        ).toContain('Tanggal awal');
    });

    test.each([
        [422, 'VALIDATION'],
        [429, 'RATE_LIMITED'],
        [500, 'SERVER_ERROR'],
    ] as const)('API error mapper memetakan HTTP %i', (status, code) => {
        expect(
            normalizeApiError({
                isAxiosError: true,
                response: { data: {}, headers: {}, status },
                toJSON: () => ({}),
            }),
        ).toMatchObject({ code, status });
    });

    test('API error mapper mempertahankan error aplikasi', () => {
        const error = createApiError('OFFLINE');
        expect(normalizeApiError(error)).toBe(error);
    });

    test('query builder menghasilkan key stabil dan terisolasi', () => {
        const params = {
            activity: 'melaspas',
            end_date: '2026-08-05',
            start_date: '2026-07-30',
        };
        expect(queryKeys.dewasa.search(params)).toEqual([
            'dewasa',
            'search',
            params,
        ]);
        expect(queryKeys.calendar.month(2026, 7)).toEqual([
            'calendar',
            'rich-v3',
            'month',
            2026,
            7,
        ]);
    });

    test('status mapper mencakup seluruh status rekomendasi', () => {
        const item = (
            status: DewasaRecommendation['status'],
            classification = '',
        ) =>
            ({
                classification,
                date: '2026-07-30',
                main_reasons: [],
                score: 70,
                status,
            } as DewasaRecommendation);
        expect(statusPresentation(item('recommended')).label).toBe('Baik');
        expect(
            statusPresentation(item('recommended', 'Cukup Baik')).label,
        ).toBe('Cukup baik');
        expect(statusPresentation(item('consideration')).label).toBe(
            'Pertimbangkan',
        );
        expect(statusPresentation(item('avoid')).label).toBe('Hindari');
    });

    test('Zustand store menyimpan draft lalu reset tanpa menyisakan filter', () => {
        const store = useAppStore.getState();
        store.setSelectedActivity('melaspas');
        store.setDateRange('2026-07-30', '2026-08-05');
        store.updateSearchDraft({ activity: 'melaspas' });
        store.setResultFilters({ minimumScore: 70 });

        useAppStore.getState().resetSearch();

        expect(useAppStore.getState()).toMatchObject({
            dateRange: { endDate: null, startDate: null },
            resultFilters: { classification: 'all', minimumScore: null },
            searchDraft: {},
            selectedActivity: null,
        });
    });

    test('auth session dapat ditandai kedaluwarsa', () => {
        useAuthStore.setState({
            isAuthenticated: true,
            user: { email: 'indra@example.test', id: 1, name: 'Indra' },
        });
        useAuthStore.getState().expireSession();
        expect(useAuthStore.getState()).toMatchObject({
            isAuthenticated: false,
            user: null,
        });
    });
});

describe('Fase 26 component tests', () => {
    test('button menjalankan aksi dan menonaktifkannya ketika loading', async () => {
        const onPress = jest.fn();
        let renderer: ReactTestRenderer.ReactTestRenderer;
        await act(() => {
            renderer = ReactTestRenderer.create(
                <AppButton label="Cari" onPress={onPress} />,
            );
        });
        await act(() => firstButton(renderer!).props.onPress());
        expect(onPress).toHaveBeenCalledTimes(1);
        await act(() =>
            renderer!.update(
                <AppButton label="Cari" loading onPress={onPress} />,
            ),
        );
        expect(firstButton(renderer!).props.disabled).toBe(true);
        await act(() => renderer!.unmount());
    });

    test('card, badge, skeleton, empty, error, dan recommendation state tampil', async () => {
        let renderer: ReactTestRenderer.ReactTestRenderer;
        await act(() => {
            renderer = ReactTestRenderer.create(
                <>
                    <AppCard>
                        <AppText>Kartu rekomendasi</AppText>
                    </AppCard>
                    <AppBadge label="Rekomendasi" tone="success" />
                    <AppSkeleton height={20} />
                    <AppEmptyState message="Belum ada data" title="Kosong" />
                    <AppErrorState message="Server bermasalah" />
                </>,
            );
        });
        expect(textContent(renderer!)).toContain('Kartu rekomendasi');
        expect(textContent(renderer!)).toContain('Rekomendasi');
        expect(textContent(renderer!)).toContain('Belum ada data');
        expect(textContent(renderer!)).toContain('Server bermasalah');
        await act(() => renderer!.unmount());
    });

    test('accordion membuka dan menutup konten', async () => {
        let renderer: ReactTestRenderer.ReactTestRenderer;
        await act(() => {
            renderer = ReactTestRenderer.create(
                <AppAccordion title="Alasan">
                    <AppText>Isi alasan</AppText>
                </AppAccordion>,
            );
        });
        expect(textContent(renderer!)).not.toContain('Isi alasan');
        await act(() => firstButton(renderer!).props.onPress());
        expect(textContent(renderer!)).toContain('Isi alasan');
        await act(() => renderer!.unmount());
    });
});

describe('Fase 26 screen smoke tests', () => {
    const cases: Array<[string, React.ReactElement, string]> = [
        [
            'Beranda',
            <HomeScreen navigation={navigation} route={{} as any} />,
            'Cari hari baik',
        ],
        [
            'Kegiatan',
            <ActivityListScreen navigation={navigation} />,
            'Kegiatan apa',
        ],
        [
            'Kegiatan untuk satu tanggal',
            <ActivityListScreen
                navigation={navigation}
                route={{ params: { date: '2026-08-17' } }}
            />,
            'Senin, 17 Agustus 2026',
        ],
        [
            'Rentang',
            <DateRangeScreen
                navigation={navigation}
                route={{ params: { activityId: 1 } } as any}
            />,
            'Kapan rencananya',
        ],
        [
            'Hasil',
            <SearchResultScreen
                navigation={navigation}
                route={
                    {
                        params: {
                            activityId: 1,
                            startDate: '2026-07-30',
                            endDate: '2026-08-05',
                        },
                    } as any
                }
            />,
            '',
        ],
        [
            'Detail',
            <DewasaDetailScreen
                navigation={navigation}
                route={
                    {
                        params: { activity: 'melaspas', date: '2026-07-30' },
                    } as any
                }
            />,
            '',
        ],
        [
            'Kalender',
            <CalendarScreen navigation={navigation} route={{} as any} />,
            'Kalender',
        ],
        [
            'Login',
            <LoginScreen navigation={navigation} route={{} as any} />,
            'Selamat datang kembali',
        ],
        [
            'Tersimpan',
            <SavedScreen navigation={navigation} route={{} as any} />,
            'Tersimpan',
        ],
    ];

    test.each(cases)('%s dapat dirender', async (_name, element, expected) => {
        let renderer: ReactTestRenderer.ReactTestRenderer;
        await act(() => {
            renderer = ReactTestRenderer.create(
                <SafeAreaProvider
                    initialMetrics={{
                        frame: { height: 800, width: 400, x: 0, y: 0 },
                        insets: { bottom: 0, left: 0, right: 0, top: 0 },
                    }}
                >
                    {element}
                </SafeAreaProvider>,
            );
        });
        expect(textContent(renderer!)).toContain(expected);
        await act(() => renderer!.unmount());
    });
});
