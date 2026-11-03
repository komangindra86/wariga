import type { BottomTabScreenProps } from '@react-navigation/bottom-tabs';
import type { CompositeScreenProps } from '@react-navigation/native';
import type { NativeStackScreenProps } from '@react-navigation/native-stack';
import { useQuery, useQueryClient } from '@tanstack/react-query';
import { format, startOfMonth } from 'date-fns';
import { id } from 'date-fns/locale';
import { useEffect, useMemo, useRef, useState } from 'react';
import {
    PanResponder,
    Pressable,
    RefreshControl,
    StyleSheet,
    View,
} from 'react-native';

import { endpoints } from '@/api/endpoints';
import { queryKeys } from '@/api/queryKeys';
import {
    AppButton,
    AppCard,
    AppEmptyState,
    AppErrorState,
    AppSkeleton,
    AppText,
    OfflineBanner,
    ScreenContainer,
} from '@/components';
import { colors, radius, spacing } from '@/theme';
import type { MainTabParamList, RootStackParamList } from '@/types/navigation';
import {
    buildMonthGrid,
    calendarMarker,
    importantDay,
    isToday,
    moveMonth,
    WEEKDAY_LABELS,
} from '@/utils/calendar';

type Props = CompositeScreenProps<
    BottomTabScreenProps<MainTabParamList, 'Calendar'>,
    NativeStackScreenProps<RootStackParamList>
>;

export function CalendarScreen({ navigation }: Props) {
    const [month, setMonth] = useState(() => startOfMonth(new Date()));
    const queryClient = useQueryClient();
    const year = month.getFullYear();
    const monthNumber = month.getMonth() + 1;
    const query = useQuery({
        queryFn: () => endpoints.calendarMonth(year, monthNumber),
        queryKey: queryKeys.calendar.month(year, monthNumber),
        staleTime: 1000 * 60 * 60,
    });
    const changeMonth = (offset: number) =>
        setMonth(current => moveMonth(current, offset));
    const changeMonthRef = useRef(changeMonth);
    changeMonthRef.current = changeMonth;
    const panResponder = useMemo(
        () =>
            PanResponder.create({
                onMoveShouldSetPanResponder: (_, gesture) =>
                    Math.abs(gesture.dx) > 18 &&
                    Math.abs(gesture.dx) > Math.abs(gesture.dy),
                onPanResponderRelease: (_, gesture) => {
                    if (gesture.dx < -50) changeMonthRef.current(1);
                    if (gesture.dx > 50) changeMonthRef.current(-1);
                },
            }),
        [],
    );

    useEffect(() => {
        if (!query.data) return;
        [-1, 1].forEach(offset => {
            const adjacent = moveMonth(month, offset);
            queryClient
                .prefetchQuery({
                    queryFn: () =>
                        endpoints.calendarMonth(
                            adjacent.getFullYear(),
                            adjacent.getMonth() + 1,
                        ),
                    queryKey: queryKeys.calendar.month(
                        adjacent.getFullYear(),
                        adjacent.getMonth() + 1,
                    ),
                    staleTime: 1000 * 60 * 60,
                })
                .catch(() => undefined);
        });
    }, [month, query.data, queryClient]);

    const cells = useMemo(
        () => buildMonthGrid(month, query.data ?? []),
        [month, query.data],
    );
    const monthlyAgenda = useMemo(
        () =>
            (query.data ?? []).filter(
                item => (item.observances?.length ?? 0) > 0,
            ),
        [query.data],
    );

    return (
        <>
            <OfflineBanner visible={query.fetchStatus === 'paused'} />
            <ScreenContainer
                refreshControl={
                    <RefreshControl
                        colors={[colors.primary]}
                        onRefresh={() => query.refetch()}
                        refreshing={query.isRefetching}
                        tintColor={colors.primary}
                    />
                }
                style={styles.container}
            >
                <View style={styles.header}>
                    <View style={styles.headerText}>
                        <AppText variant="title" weight="bold">
                            Kalender Bali
                        </AppText>
                        <AppText color="textMuted" variant="bodySmall">
                            Geser ke kiri atau kanan untuk ganti bulan. Ketuk
                            tanggal untuk rinciannya.
                        </AppText>
                    </View>
                    <AppButton
                        label="Hari ini"
                        onPress={() => setMonth(startOfMonth(new Date()))}
                        variant="outline"
                    />
                </View>

                <AppCard style={styles.calendar} {...panResponder.panHandlers}>
                    <View style={styles.monthHeader}>
                        <Pressable
                            accessibilityLabel="Bulan sebelumnya"
                            hitSlop={12}
                            onPress={() => changeMonth(-1)}
                        >
                            <AppText variant="title" weight="bold">
                                ‹
                            </AppText>
                        </Pressable>
                        <AppText variant="titleSmall" weight="semibold">
                            {format(month, 'MMMM yyyy', { locale: id })}
                        </AppText>
                        <Pressable
                            accessibilityLabel="Bulan berikutnya"
                            hitSlop={12}
                            onPress={() => changeMonth(1)}
                        >
                            <AppText variant="title" weight="bold">
                                ›
                            </AppText>
                        </Pressable>
                    </View>

                    {query.isPending ? (
                        <CalendarLoading />
                    ) : query.isError ? (
                        <AppErrorState
                            message={query.error.message}
                            onRetry={() => query.refetch()}
                        />
                    ) : query.data.length === 0 ? (
                        <AppEmptyState
                            message="Data untuk bulan ini belum tersedia."
                            title="Kalender kosong"
                        />
                    ) : (
                        <>
                            <View style={styles.grid}>
                                {WEEKDAY_LABELS.map(label => (
                                    <View key={label} style={styles.weekday}>
                                        <AppText
                                            color="textMuted"
                                            variant="caption"
                                            weight="semibold"
                                        >
                                            {label}
                                        </AppText>
                                    </View>
                                ))}
                                {cells.map((cell, index) =>
                                    cell === null ? (
                                        <View
                                            key={`empty-${index}`}
                                            style={styles.day}
                                        />
                                    ) : (
                                        <CalendarDay
                                            key={cell.key}
                                            cell={cell}
                                            onPress={() =>
                                                navigation.navigate(
                                                    'CalendarDetail',
                                                    { date: cell.key },
                                                )
                                            }
                                        />
                                    ),
                                )}
                            </View>
                            <View style={styles.legend}>
                                <LegendDot
                                    color={colors.danger}
                                    label="Purnama"
                                />
                                <LegendDot color={colors.text} label="Tilem" />
                                <LegendDot
                                    color={colors.danger}
                                    label="Rahina Bali"
                                    small
                                />
                            </View>
                        </>
                    )}
                </AppCard>
                {monthlyAgenda.length > 0 ? (
                    <View style={styles.agenda}>
                        <AppText variant="titleSmall" weight="semibold">
                            Rahina dan tanggal penting bulan ini
                        </AppText>
                        {monthlyAgenda.map(item => (
                            <Pressable
                                key={item.date}
                                onPress={() =>
                                    navigation.navigate('CalendarDetail', {
                                        date: item.date,
                                    })
                                }
                            >
                                <AppCard style={styles.agendaItem}>
                                    <View style={styles.agendaDate}>
                                        <AppText
                                            color={
                                                item.is_red_date
                                                    ? 'danger'
                                                    : 'primary'
                                            }
                                            variant="titleSmall"
                                            weight="bold"
                                        >
                                            {format(
                                                new Date(
                                                    `${item.date}T00:00:00`,
                                                ),
                                                'd',
                                            )}
                                        </AppText>
                                        <AppText
                                            color="textMuted"
                                            variant="caption"
                                        >
                                            {format(
                                                new Date(
                                                    `${item.date}T00:00:00`,
                                                ),
                                                'MMM',
                                                { locale: id },
                                            )}
                                        </AppText>
                                    </View>
                                    <View style={styles.agendaNames}>
                                        {item.observances?.map(observance => (
                                            <AppText
                                                key={`${item.date}-${observance.name}`}
                                                variant="bodySmall"
                                                weight="semibold"
                                            >
                                                {observance.name}
                                            </AppText>
                                        ))}
                                    </View>
                                </AppCard>
                            </Pressable>
                        ))}
                    </View>
                ) : null}
            </ScreenContainer>
        </>
    );
}

function CalendarDay({
    cell,
    onPress,
}: {
    cell: NonNullable<ReturnType<typeof buildMonthGrid>[number]>;
    onPress: () => void;
}) {
    const important = importantDay(cell.item);
    const today = isToday(cell.date);
    const isSunday = cell.date.getDay() === 0;
    const marker = calendarMarker(cell.item);

    return (
        <Pressable
            accessibilityLabel={`${format(cell.date, 'd MMMM yyyy', {
                locale: id,
            })}${cell.item?.pancawara ? `, ${cell.item.pancawara}` : ''}${
                important ? `, ${important}` : ''
            }`}
            onPress={onPress}
            style={({ pressed }) => [
                styles.day,
                today && styles.today,
                pressed && styles.dayPressed,
            ]}
        >
            <View style={styles.dateSlot}>
                <AppText
                    color={
                        cell.item?.is_red_date || isSunday
                            ? 'danger'
                            : today
                            ? 'info'
                            : 'text'
                    }
                    style={styles.dateNumber}
                    variant="bodySmall"
                    weight="semibold"
                >
                    {format(cell.date, 'd')}
                </AppText>
            </View>
            <AppText
                color="textMuted"
                numberOfLines={1}
                style={styles.waraText}
                variant="caption"
            >
                {cell.item?.triwara ?? '—'}
            </AppText>
            <AppText
                color="textMuted"
                numberOfLines={1}
                style={styles.waraText}
                variant="caption"
            >
                {cell.item?.pancawara ?? '—'}
            </AppText>
            <View style={styles.markerSlot}>
                {marker === 'purnama' ? (
                    <View
                        accessibilityLabel="Purnama"
                        style={[styles.phaseDot, styles.purnamaDot]}
                    />
                ) : marker === 'tilem' ? (
                    <View
                        accessibilityLabel="Tilem"
                        style={[styles.phaseDot, styles.tilemDot]}
                    />
                ) : marker === 'rahina' ? (
                    <View
                        accessibilityLabel="Rahina Bali"
                        style={[styles.rahinaDot, styles.purnamaDot]}
                    />
                ) : null}
            </View>
        </Pressable>
    );
}

function LegendDot({
    color,
    label,
    small = false,
}: {
    color: string;
    label: string;
    small?: boolean;
}) {
    return (
        <View style={styles.legendItem}>
            <View
                style={[
                    styles.legendDot,
                    small && styles.legendDotSmall,
                    { backgroundColor: color },
                ]}
            />
            <AppText color="textMuted" variant="caption">
                {label}
            </AppText>
        </View>
    );
}

function CalendarLoading() {
    return (
        <View style={styles.loading}>
            <AppSkeleton height={22} />
            <AppSkeleton height={210} />
        </View>
    );
}

const styles = StyleSheet.create({
    agenda: { gap: spacing.sm },
    agendaDate: { alignItems: 'center', width: 36 },
    agendaItem: {
        alignItems: 'center',
        backgroundColor: colors.surface,
        borderRadius: radius.lg,
        flexDirection: 'row',
        gap: spacing.md,
        padding: spacing.lg,
    },
    agendaNames: { flex: 1, gap: spacing.xxs },
    calendar: { gap: spacing.xl, paddingHorizontal: spacing.sm },
    container: { gap: spacing.lg },
    day: {
        alignItems: 'center',
        borderRadius: radius.lg,
        flexBasis: '14.2857%',
        height: 72,
        justifyContent: 'flex-start',
        paddingHorizontal: spacing.xxs,
        paddingTop: spacing.xs,
    },
    dayPressed: { backgroundColor: colors.surfaceMuted },
    grid: { flexDirection: 'row', flexWrap: 'wrap' },
    header: {
        alignItems: 'center',
        flexDirection: 'row',
        gap: spacing.md,
        justifyContent: 'space-between',
    },
    headerText: { flex: 1, gap: spacing.xs },
    dateNumber: { textAlign: 'center', width: '100%' },
    dateSlot: { height: 20, justifyContent: 'center', width: '100%' },
    legend: {
        flexDirection: 'row',
        flexWrap: 'wrap',
        gap: spacing.sm,
        paddingHorizontal: spacing.sm,
    },
    legendDot: { borderRadius: 5, height: 10, width: 10 },
    legendDotSmall: { borderRadius: 3, height: 6, width: 6 },
    legendItem: {
        alignItems: 'center',
        flexDirection: 'row',
        gap: spacing.xs,
    },
    loading: { gap: spacing.md },
    markerSlot: {
        alignItems: 'center',
        height: 10,
        justifyContent: 'center',
        width: '100%',
    },
    monthHeader: {
        alignItems: 'center',
        flexDirection: 'row',
        justifyContent: 'space-between',
        paddingHorizontal: spacing.md,
    },
    phaseDot: { borderRadius: 5, height: 10, width: 10 },
    purnamaDot: { backgroundColor: colors.danger },
    rahinaDot: { borderRadius: 3, height: 6, width: 6 },
    tilemDot: { backgroundColor: colors.text },
    today: {
        backgroundColor: colors.primarySoft,
        borderColor: colors.primary,
        borderWidth: StyleSheet.hairlineWidth,
    },
    weekday: {
        alignItems: 'center',
        flexBasis: '14.2857%',
        paddingVertical: spacing.sm,
    },
    waraText: {
        fontSize: 9,
        height: 14,
        lineHeight: 12,
        textAlign: 'center',
        width: '100%',
    },
});
