import type { NativeStackScreenProps } from '@react-navigation/native-stack';
import { useQuery } from '@tanstack/react-query';
import { format, parseISO } from 'date-fns';
import { id } from 'date-fns/locale';
import { useMemo, useState } from 'react';
import { Pressable, StyleSheet, View } from 'react-native';

import { endpoints } from '@/api/endpoints';
import { queryKeys } from '@/api/queryKeys';
import {
    AppBadge,
    AppBottomSheet,
    AppButton,
    AppCard,
    AppEmptyState,
    AppErrorState,
    AppSkeleton,
    AppText,
    ScreenContainer,
} from '@/components';
import { colors, radius, spacing } from '@/theme';
import type { DewasaSearchParams } from '@/types/domain';
import type { RootStackParamList } from '@/types/navigation';
import {
    matchesResultFilter,
    statusPresentation,
    type ResultFilter,
} from '@/utils/dewasaResults';

type Props = NativeStackScreenProps<RootStackParamList, 'SearchResult'>;
type SortOption = NonNullable<DewasaSearchParams['sort']>;

const filters: Array<{ key: ResultFilter; label: string }> = [
    { key: 'all', label: 'Semua' },
    { key: 'recommended', label: 'Rekomendasi' },
    { key: 'good', label: 'Cukup baik' },
    { key: 'consideration', label: 'Pertimbangan' },
    { key: 'avoid', label: 'Hindari' },
];
const sorts: Array<{ key: SortOption; label: string }> = [
    { key: 'score_desc', label: 'Skor tertinggi' },
    { key: 'date_asc', label: 'Tanggal terdekat' },
    { key: 'date_desc', label: 'Tanggal terlama' },
];

export function SearchResultScreen({ navigation, route }: Props) {
    const { activityId, endDate, startDate } = route.params ?? {};
    const [filter, setFilter] = useState<ResultFilter>('all');
    const [sort, setSort] = useState<SortOption>('score_desc');
    const [sheetOpen, setSheetOpen] = useState(false);
    const params =
        activityId && startDate && endDate
            ? {
                  activity: String(activityId),
                  end_date: endDate,
                  limit: 100,
                  mode: 'complete' as const,
                  sort,
                  start_date: startDate,
              }
            : null;
    const results = useQuery({
        enabled: Boolean(params),
        queryFn: () => endpoints.searchDewasa(params!),
        queryKey: params
            ? queryKeys.dewasa.search(params)
            : [...queryKeys.dewasa.all, 'invalid-search'],
    });
    const visible = useMemo(
        () =>
            (results.data?.recommendations ?? []).filter(item =>
                matchesResultFilter(item, filter),
            ),
        [filter, results.data?.recommendations],
    );

    if (!params) {
        return (
            <ScreenContainer>
                <AppErrorState message="Kegiatan atau rentang tanggal belum lengkap." />
            </ScreenContainer>
        );
    }

    return (
        <>
            <ScreenContainer>
                {results.isPending ? (
                    <LoadingState />
                ) : results.isError ? (
                    <AppErrorState
                        message={results.error.message}
                        onRetry={() => results.refetch().catch(() => undefined)}
                    />
                ) : (
                    <>
                        <AppText variant="title" weight="bold">
                            {results.data.activity.name}
                        </AppText>
                        <AppText color="textMuted">
                            {dateLabel(params.start_date)} –{' '}
                            {dateLabel(params.end_date)}
                        </AppText>
                        <Summary
                            avoid={results.data.summary.avoid_count}
                            checked={results.data.summary.total_dates_checked}
                            consideration={
                                results.data.summary.consideration_count
                            }
                            recommended={results.data.summary.recommended_count}
                        />
                        <View style={styles.toolbar}>
                            <AppText color="textMuted" variant="bodySmall">
                                {visible.length} hasil ditampilkan
                            </AppText>
                            <AppButton
                                label="Filter & urutkan"
                                onPress={() => setSheetOpen(true)}
                                variant="outline"
                            />
                        </View>
                        {visible.length === 0 ? (
                            <AppEmptyState
                                actionLabel="Tampilkan semua"
                                message="Tidak ada tanggal yang cocok dengan filter ini."
                                onAction={() => setFilter('all')}
                                title="Hasil tidak ditemukan"
                            />
                        ) : (
                            <View style={styles.list}>
                                {visible.map(item => {
                                    const badge = statusPresentation(item);
                                    return (
                                        <Pressable
                                            accessibilityHint="Buka detail rekomendasi"
                                            accessibilityRole="button"
                                            key={item.date}
                                            onPress={() =>
                                                navigation.navigate(
                                                    'DewasaDetail',
                                                    {
                                                        activity:
                                                            results.data
                                                                .activity.slug,
                                                        date: item.date,
                                                    },
                                                )
                                            }
                                        >
                                            {({ pressed }) => (
                                                <AppCard
                                                    style={[
                                                        styles.resultCard,
                                                        pressed &&
                                                            styles.pressed,
                                                    ]}
                                                >
                                                    <View
                                                        style={
                                                            styles.cardHeader
                                                        }
                                                    >
                                                        <View
                                                            style={
                                                                styles.cardTitle
                                                            }
                                                        >
                                                            <AppText
                                                                variant="titleSmall"
                                                                weight="semibold"
                                                            >
                                                                {dateLabel(
                                                                    item.date,
                                                                )}
                                                            </AppText>
                                                            <AppText
                                                                color="primary"
                                                                weight="bold"
                                                            >
                                                                Skor{' '}
                                                                {item.score}
                                                            </AppText>
                                                        </View>
                                                        <AppBadge
                                                            label={badge.label}
                                                            tone={badge.tone}
                                                        />
                                                    </View>
                                                    <AppText
                                                        color="textMuted"
                                                        variant="bodySmall"
                                                    >
                                                        {calendarLabel(item)}
                                                    </AppText>
                                                    <View
                                                        style={styles.reasons}
                                                    >
                                                        {item.main_reasons
                                                            .slice(0, 3)
                                                            .map(reason => (
                                                                <AppText
                                                                    key={
                                                                        reason.rule_id
                                                                    }
                                                                    variant="bodySmall"
                                                                >
                                                                    •{' '}
                                                                    {
                                                                        reason.reason
                                                                    }
                                                                </AppText>
                                                            ))}
                                                        {item.main_reasons
                                                            .length === 0 ? (
                                                            <AppText
                                                                color="textMuted"
                                                                variant="bodySmall"
                                                            >
                                                                Belum ada alasan
                                                                utama.
                                                            </AppText>
                                                        ) : null}
                                                    </View>
                                                </AppCard>
                                            )}
                                        </Pressable>
                                    );
                                })}
                            </View>
                        )}
                    </>
                )}
            </ScreenContainer>
            <AppBottomSheet
                onClose={() => setSheetOpen(false)}
                title="Filter dan urutan"
                visible={sheetOpen}
            >
                <OptionGroup
                    label="Tampilkan"
                    onSelect={value => setFilter(value as ResultFilter)}
                    options={filters}
                    selected={filter}
                />
                <OptionGroup
                    label="Urutkan"
                    onSelect={value => setSort(value as SortOption)}
                    options={sorts}
                    selected={sort}
                />
                <AppButton
                    fullWidth
                    label="Terapkan"
                    onPress={() => setSheetOpen(false)}
                />
            </AppBottomSheet>
        </>
    );
}

function dateLabel(date: string) {
    return format(parseISO(date), 'd MMMM yyyy', { locale: id });
}

function calendarLabel(item: {
    calendar?: {
        wuku?: { nama: string } | null;
        wewaran?: {
            pancawara?: { nama: string } | null;
            saptawara?: { nama: string } | null;
        };
    };
}) {
    const calendar = item.calendar;
    return [
        calendar?.wewaran?.saptawara?.nama,
        calendar?.wewaran?.pancawara?.nama,
        calendar?.wuku?.nama ? `Wuku ${calendar.wuku.nama}` : null,
    ]
        .filter(Boolean)
        .join(' • ');
}

function Summary({
    avoid,
    checked,
    consideration,
    recommended,
}: {
    avoid: number;
    checked: number;
    consideration: number;
    recommended: number;
}) {
    const items = [
        ['Diperiksa', checked],
        ['Rekomendasi', recommended],
        ['Pertimbangan', consideration],
        ['Hindari', avoid],
    ];
    return (
        <AppCard style={styles.summary}>
            {items.map(([label, value]) => (
                <View key={label} style={styles.summaryItem}>
                    <AppText variant="titleSmall" weight="bold">
                        {value}
                    </AppText>
                    <AppText color="textMuted" variant="caption">
                        {label}
                    </AppText>
                </View>
            ))}
        </AppCard>
    );
}

function OptionGroup({
    label,
    onSelect,
    options,
    selected,
}: {
    label: string;
    onSelect: (value: string) => void;
    options: Array<{ key: string; label: string }>;
    selected: string;
}) {
    return (
        <View style={styles.optionGroup}>
            <AppText weight="semibold">{label}</AppText>
            <View style={styles.options}>
                {options.map(option => (
                    <Pressable
                        accessibilityRole="radio"
                        accessibilityState={{
                            checked: selected === option.key,
                        }}
                        key={option.key}
                        onPress={() => onSelect(option.key)}
                        style={[
                            styles.option,
                            selected === option.key && styles.optionSelected,
                        ]}
                    >
                        <AppText
                            color={
                                selected === option.key ? 'textInverse' : 'text'
                            }
                            variant="bodySmall"
                        >
                            {option.label}
                        </AppText>
                    </Pressable>
                ))}
            </View>
        </View>
    );
}

function LoadingState() {
    return (
        <View style={styles.list}>
            <AppSkeleton height={32} width="70%" />
            <AppSkeleton height={116} />
            <AppSkeleton height={210} />
            <AppSkeleton height={210} />
        </View>
    );
}

const styles = StyleSheet.create({
    cardHeader: {
        alignItems: 'flex-start',
        flexDirection: 'row',
        gap: spacing.md,
        justifyContent: 'space-between',
    },
    cardTitle: { flex: 1, gap: spacing.xs },
    list: { gap: spacing.lg },
    option: {
        borderColor: colors.border,
        borderRadius: radius.full,
        borderWidth: 1,
        paddingHorizontal: spacing.lg,
        paddingVertical: spacing.md,
    },
    optionGroup: { gap: spacing.sm },
    optionSelected: {
        backgroundColor: colors.primary,
        borderColor: colors.primary,
    },
    options: { flexDirection: 'row', flexWrap: 'wrap', gap: spacing.sm },
    pressed: { opacity: 0.82 },
    reasons: { gap: spacing.xs, marginTop: spacing.md },
    resultCard: { gap: spacing.sm },
    summary: {
        backgroundColor: colors.primarySoft,
        borderColor: colors.primarySoft,
        flexDirection: 'row',
        flexWrap: 'wrap',
        gap: spacing.lg,
        justifyContent: 'space-between',
    },
    summaryItem: { alignItems: 'center', minWidth: 68 },
    toolbar: {
        alignItems: 'center',
        flexDirection: 'row',
        justifyContent: 'space-between',
    },
});
