import React from 'react';
import { Text } from 'react-native';
import { SafeAreaProvider } from 'react-native-safe-area-context';
import ReactTestRenderer, { act } from 'react-test-renderer';

const explanation = {
    summary: 'Sebaiknya dihindari untuk Melaspas karena ada Geheng Manyinget.',
    points: [
        'Geheng Manyinget (Buda Pangelong 10) jatuh pada tanggal ini.',
        'Hari ini juga Hari Raya Pagerwesi. Rerainan adalah hari suci untuk sembahyang, tetapi tidak otomatis menjadi hari baik untuk Melaspas.',
    ],
};
const reason = {
    alahing_label: 'penanggal/pangelong (hitungan hari dalam bulan Bali)',
    alahing_priority: 2,
    matched: true,
    matched_composition: 'Buda Pangelong 10',
    reason: 'Tidak baik untuk segala pekerjaan yang penting-penting.',
    rule_code: 'KB-MASTER-143',
    rule_id: 143,
    rule_name: 'Geheng Manyinget',
    rule_type: 'prohibition',
    score_effect: -30,
    severity: 'high',
};
const recommendation = {
    classification: 'Sebaiknya Dihindari',
    date: '2026-11-04',
    explanation,
    main_reasons: [reason],
    score: 10,
    status: 'avoid',
};
let mockData: unknown;

jest.mock('@tanstack/react-query', () => ({
    ...jest.requireActual('@tanstack/react-query'),
    useMutation: jest.fn(() => ({ isPending: false, mutate: jest.fn() })),
    useQuery: jest.fn(() => ({
        data: mockData,
        isError: false,
        isPending: false,
        refetch: jest.fn(),
    })),
    useQueryClient: jest.fn(() => ({ invalidateQueries: jest.fn() })),
}));

import { DewasaDetailScreen } from '@/screens/DewasaDetailScreen';
import { SearchResultScreen } from '@/screens/SearchResultScreen';

const navigation = { navigate: jest.fn() } as any;

function textContent(renderer: ReactTestRenderer.ReactTestRenderer) {
    return renderer.root
        .findAllByType(Text)
        .map(node => node.props.children)
        .flat(Infinity)
        .filter(value => typeof value === 'string')
        .join(' ');
}

async function render(element: React.ReactElement) {
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
    return renderer!;
}

describe('Fase 27 penjelasan hasil dewasa', () => {
    test('detail menampilkan penjelasan bahasa sederhana dan kekuatan unsur', async () => {
        mockData = {
            ...recommendation,
            activity: { id: 4, name: 'Melaspas', slug: 'melaspas' },
            rules: [reason],
        };
        const renderer = await render(
            <DewasaDetailScreen
                navigation={navigation}
                route={
                    {
                        params: { activity: 'melaspas', date: '2026-11-04' },
                    } as any
                }
            />,
        );
        const text = textContent(renderer);

        expect(text).toContain('Kenapa hasilnya begini?');
        expect(text).toContain(explanation.summary);
        expect(text).toContain('Hari ini juga Hari Raya Pagerwesi');
        expect(text).toContain('Apa itu alahing dewasa?');
        await act(() => renderer.unmount());
    });

    test('kartu hasil pencarian memakai ringkasan penjelasan', async () => {
        mockData = {
            activity: { id: 4, name: 'Melaspas', slug: 'melaspas' },
            recommendations: [recommendation],
            summary: {
                avoid_count: 1,
                consideration_count: 0,
                recommended_count: 0,
                total_dates_checked: 1,
            },
        };
        const renderer = await render(
            <SearchResultScreen
                navigation={navigation}
                route={
                    {
                        params: {
                            activityId: 4,
                            endDate: '2026-11-04',
                            startDate: '2026-11-04',
                        },
                    } as any
                }
            />,
        );
        const text = textContent(renderer);

        expect(text).toContain(explanation.summary);
        expect(text).not.toContain(reason.reason);
        await act(() => renderer.unmount());
    });
});
