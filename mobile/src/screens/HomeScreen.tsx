import type { BottomTabScreenProps } from '@react-navigation/bottom-tabs';
import type { CompositeScreenProps } from '@react-navigation/native';
import type { NativeStackScreenProps } from '@react-navigation/native-stack';
import { useQuery, type UseQueryResult } from '@tanstack/react-query';
import { format, parseISO } from 'date-fns';
import { id } from 'date-fns/locale';
import { useMemo } from 'react';
import { Pressable, RefreshControl, StyleSheet, View } from 'react-native';

import { endpoints } from '@/api/endpoints';
import { queryKeys } from '@/api/queryKeys';
import {
    AppAccordion,
    AppBadge,
    AppButton,
    AppCard,
    AppErrorState,
    AppSkeleton,
    AppText,
    OfflineBanner,
    ScreenContainer,
} from '@/components';
import { useAuthStore } from '@/store/authStore';
import { colors, radius, spacing } from '@/theme';
import type { BaliCalendarDate, DailyRecommendations } from '@/types/domain';
import type { MainTabParamList, RootStackParamList } from '@/types/navigation';
import { lunarPhaseLabel } from '@/utils/calendar';

type Props = CompositeScreenProps<
    BottomTabScreenProps<MainTabParamList, 'Home'>,
    NativeStackScreenProps<RootStackParamList>
>;

export function HomeScreen({ navigation }: Props) {
    const isAuthenticated = useAuthStore(state => state.isAuthenticated);
    const calendar = useQuery({
        queryFn: endpoints.calendarToday,
        queryKey: queryKeys.calendar.today(),
    });
    const activities = useQuery({
        queryFn: endpoints.activities,
        queryKey: queryKeys.activities.all,
    });
    const daily = useQuery({
        queryFn: endpoints.dailyRecommendations,
        queryKey: queryKeys.dewasa.today(),
    });
    const saved = useQuery({
        enabled: isAuthenticated,
        queryFn: endpoints.savedDewasa,
        queryKey: queryKeys.saved.all,
    });

    const popularActivities = useMemo(() => {
        const all = activities.data ?? [];
        const popular = all.filter(activity => activity.is_popular);
        // Bila admin belum menandai kegiatan populer, tetap tampilkan pilihan cepat.
        return (popular.length > 0 ? popular : all).slice(0, 4);
    }, [activities.data]);
    const isRefreshing =
        calendar.isRefetching ||
        daily.isRefetching ||
        activities.isRefetching ||
        (isAuthenticated && saved.isRefetching);
    const refresh = () => {
        calendar.refetch().catch(() => undefined);
        daily.refetch().catch(() => undefined);
        activities.refetch().catch(() => undefined);
        if (isAuthenticated) saved.refetch().catch(() => undefined);
    };
    const latestSaved = isAuthenticated ? saved.data?.[0] : undefined;

    return (
        <>
            <OfflineBanner visible={calendar.fetchStatus === 'paused'} />
            <ScreenContainer
                refreshControl={
                    <RefreshControl
                        colors={[colors.primary]}
                        onRefresh={refresh}
                        refreshing={isRefreshing}
                        tintColor={colors.primary}
                    />
                }
            >
                <TodayHeader calendar={calendar} />

                <View style={styles.searchCard}>
                    <AppText color="textInverse" variant="title" weight="bold">
                        Cari hari baik
                    </AppText>
                    <AppText color="secondarySoft">
                        Mau menikah, ngaben, membangun rumah, atau memulai
                        usaha? Pilih kegiatannya, kami carikan tanggal yang baik
                        beserta alasannya.
                    </AppText>
                    <AppButton
                        fullWidth
                        label="Mulai cari hari baik"
                        onPress={() => navigation.navigate('Search')}
                        variant="secondary"
                    />
                </View>

                {popularActivities.length > 0 ? (
                    <>
                        <SectionTitle
                            hint="Ketuk untuk langsung memilih tanggal."
                            title="Sering dicari"
                        />
                        <View style={styles.activityGrid}>
                            {popularActivities.map(activity => (
                                <Pressable
                                    accessibilityHint="Lanjut memilih rentang waktu"
                                    accessibilityRole="button"
                                    key={activity.id}
                                    onPress={() =>
                                        navigation.navigate('DateRange', {
                                            activityId: activity.id,
                                        })
                                    }
                                    style={({ pressed }) => [
                                        styles.activity,
                                        pressed && styles.pressed,
                                    ]}
                                >
                                    <AppText style={styles.activityIcon}>
                                        {activity.icon ?? '◇'}
                                    </AppText>
                                    <AppText
                                        numberOfLines={2}
                                        weight="semibold"
                                    >
                                        {activity.name}
                                    </AppText>
                                </Pressable>
                            ))}
                        </View>
                    </>
                ) : activities.isPending ? (
                    <AppSkeleton height={92} />
                ) : null}

                <SectionTitle title="Hari ini baik untuk apa?" />
                {daily.isPending ? (
                    <DailySkeleton />
                ) : daily.isError ? (
                    <AppCard>
                        <AppErrorState
                            message={daily.error.message}
                            onRetry={() =>
                                daily.refetch().catch(() => undefined)
                            }
                        />
                    </AppCard>
                ) : (
                    <DailyRecommendationCard
                        data={daily.data}
                        onSelect={(slug, date) =>
                            navigation.navigate('DewasaDetail', {
                                activity: slug,
                                date,
                            })
                        }
                    />
                )}

                {calendar.data ? (
                    <View style={styles.details}>
                        <AppAccordion title="Rincian Kalender Bali hari ini">
                            <CalendarDetails calendar={calendar.data} />
                        </AppAccordion>
                    </View>
                ) : null}

                {latestSaved ? (
                    <>
                        <SectionTitle title="Terakhir disimpan" />
                        <AppCard style={styles.card}>
                            <AppText weight="semibold">
                                {latestSaved.activity?.name ?? 'Hari baik'}
                            </AppText>
                            <AppText color="textMuted" variant="bodySmall">
                                {format(
                                    parseISO(latestSaved.date),
                                    'EEEE, d MMMM yyyy',
                                    {
                                        locale: id,
                                    },
                                )}
                            </AppText>
                            <AppButton
                                label="Lihat hasil"
                                onPress={() =>
                                    navigation.navigate('SavedDetail', {
                                        savedId: latestSaved.id,
                                    })
                                }
                                variant="ghost"
                            />
                        </AppCard>
                    </>
                ) : null}

                <View style={styles.footer}>
                    <AppText color="textMuted" variant="bodySmall">
                        Hasil Wariga adalah panduan budaya, bukan keputusan
                        mutlak. Untuk upacara penting, tanyakan juga kepada
                        sulinggih, pemangku, atau ahli Wariga.
                    </AppText>
                    <AppButton
                        label="Baca selengkapnya"
                        onPress={() => navigation.navigate('Disclaimer')}
                        variant="ghost"
                    />
                </View>
            </ScreenContainer>
        </>
    );
}

function TodayHeader({
    calendar,
}: {
    calendar: UseQueryResult<BaliCalendarDate, Error>;
}) {
    const data = calendar.data;
    const baliDay = data
        ? [
              [data.saptawara, data.pancawara].filter(Boolean).join(' '),
              data.wuku ? `Wuku ${data.wuku}` : null,
          ]
              .filter(Boolean)
              .join(' · ')
        : null;
    const lunar = data?.lunar_available
        ? [lunarPhaseLabel(data), data.sasih ? `Sasih ${data.sasih}` : null]
              .filter(Boolean)
              .join(' · ')
        : null;
    const rahina = [
        ...(data?.is_full_moon ? ['Purnama'] : []),
        ...(data?.is_new_moon ? ['Tilem'] : []),
        ...(data?.observances
            ?.filter(item => item.type !== 'moon_phase')
            .map(item => item.name) ?? []),
    ];

    return (
        <View style={styles.header}>
            <AppText color="warning" weight="semibold">
                Om Swastyastu
            </AppText>
            <AppText variant="title" weight="bold">
                {format(new Date(), 'EEEE, d MMMM yyyy', { locale: id })}
            </AppText>
            {calendar.isPending ? (
                <AppSkeleton height={22} width="70%" />
            ) : calendar.isError ? (
                <AppErrorState
                    message={calendar.error.message}
                    onRetry={() => calendar.refetch().catch(() => undefined)}
                />
            ) : (
                <>
                    {baliDay ? (
                        <AppText weight="semibold">{baliDay}</AppText>
                    ) : null}
                    {lunar ? (
                        <AppText color="textMuted">{lunar}</AppText>
                    ) : null}
                    {rahina.length > 0 ? (
                        <View style={styles.rahinaList}>
                            {rahina.map(name => (
                                <AppBadge
                                    key={name}
                                    label={name}
                                    tone="warning"
                                />
                            ))}
                        </View>
                    ) : null}
                </>
            )}
        </View>
    );
}

function SectionTitle({ title, hint }: { title: string; hint?: string }) {
    return (
        <View style={styles.sectionTitle}>
            <AppText variant="titleSmall" weight="bold">
                {title}
            </AppText>
            {hint ? (
                <AppText color="textMuted" variant="bodySmall">
                    {hint}
                </AppText>
            ) : null}
        </View>
    );
}

function DailySkeleton() {
    return (
        <AppCard style={styles.dailyCard}>
            <AppSkeleton height={22} width="65%" />
            <AppSkeleton height={48} />
            <AppSkeleton height={48} />
        </AppCard>
    );
}

function DailyRecommendationCard({
    data,
    onSelect,
}: {
    data: DailyRecommendations;
    onSelect: (slug: string, date: string) => void;
}) {
    return (
        <AppCard style={styles.dailyCard}>
            <RecommendationGroup
                empty="Belum ada kegiatan yang sangat cocok hari ini."
                items={data.recommended}
                label="Baik dilakukan"
                onSelect={slug => onSelect(slug, data.date)}
                tone="good"
            />
            <View style={styles.divider} />
            <RecommendationGroup
                empty="Tidak ada pantangan kuat hari ini."
                items={data.avoid}
                label="Sebaiknya dihindari"
                onSelect={slug => onSelect(slug, data.date)}
                tone="avoid"
            />
            <AppText color="textMuted" variant="bodySmall">
                Ketuk kegiatan untuk melihat alasannya.
            </AppText>
        </AppCard>
    );
}

function RecommendationGroup({
    items,
    label,
    empty,
    tone,
    onSelect,
}: {
    items: DailyRecommendations['recommended'];
    label: string;
    empty: string;
    tone: 'good' | 'avoid';
    onSelect: (slug: string) => void;
}) {
    return (
        <View style={styles.recommendationGroup}>
            <AppText
                color={tone === 'good' ? 'success' : 'danger'}
                weight="semibold"
            >
                {tone === 'good' ? '✓' : '✕'} {label}
            </AppText>
            {items.length === 0 ? (
                <AppText color="textMuted" variant="bodySmall">
                    {empty}
                </AppText>
            ) : (
                <View style={styles.recommendationList}>
                    {items.map(item => (
                        <Pressable
                            accessibilityRole="button"
                            key={item.id}
                            onPress={() => onSelect(item.slug)}
                            style={({ pressed }) => [
                                styles.recommendationPill,
                                tone === 'good'
                                    ? styles.goodPill
                                    : styles.avoidPill,
                                pressed && styles.pressed,
                            ]}
                        >
                            <AppText variant="bodySmall" weight="semibold">
                                {item.icon ? `${item.icon} ` : ''}
                                {item.name}
                            </AppText>
                        </Pressable>
                    ))}
                </View>
            )}
        </View>
    );
}

function CalendarDetails({ calendar }: { calendar: BaliCalendarDate }) {
    const verified = calendar.verification_status === 'verified';

    return (
        <View style={styles.card}>
            <CalendarRow
                label="Wuku"
                meaning="Nama pekan dalam kalender Bali (ada 30 wuku)."
                value={calendar.wuku}
            />
            <CalendarRow
                label="Sapta Wara"
                meaning={saptaMeaning(calendar.saptawara)}
                value={calendar.saptawara}
            />
            <CalendarRow
                label="Panca Wara"
                meaning="Hari pasaran lima harian."
                value={calendar.pancawara}
            />
            <CalendarRow
                label="Tri Wara"
                meaning="Hari pasaran tiga harian."
                value={calendar.triwara}
            />
            <CalendarRow
                label="Sad Wara"
                meaning="Hari pasaran enam harian."
                value={calendar.sadwara}
            />
            {calendar.lunar_available ? (
                <>
                    <CalendarRow
                        label="Penanggal/Pangelong"
                        meaning={
                            calendar.penanggal
                                ? 'Hitungan hari dari Tilem menuju Purnama.'
                                : 'Hitungan hari dari Purnama menuju Tilem.'
                        }
                        value={lunarPhaseLabel(calendar)}
                    />
                    <CalendarRow
                        label="Sasih"
                        meaning="Bulan dalam kalender Bali, mengikuti peredaran bulan."
                        value={calendar.sasih}
                    />
                </>
            ) : (
                <AppText color="textMuted" variant="bodySmall">
                    {calendar.lunar_message ??
                        'Data sasih dan fase bulan belum tersedia.'}
                </AppText>
            )}
            <AppBadge
                label={
                    verified
                        ? 'Sudah dicocokkan dengan rujukan'
                        : 'Hasil perhitungan'
                }
                tone={verified ? 'success' : 'info'}
            />
        </View>
    );
}

function CalendarRow({
    label,
    value,
    meaning,
}: {
    label: string;
    value?: string | null;
    meaning?: string | null;
}) {
    return (
        <View style={styles.calendarItem}>
            <View style={styles.row}>
                <AppText color="textMuted">{label}</AppText>
                <AppText weight="semibold">{value ?? 'Belum tersedia'}</AppText>
            </View>
            {meaning ? (
                <AppText color="textMuted" variant="bodySmall">
                    {meaning}
                </AppText>
            ) : null}
        </View>
    );
}

function saptaMeaning(name?: string | null) {
    const dayNames: Record<string, string> = {
        Redite: 'Minggu',
        Soma: 'Senin',
        Anggara: 'Selasa',
        Buda: 'Rabu',
        Wraspati: 'Kamis',
        Sukra: 'Jumat',
        Saniscara: 'Sabtu',
    };
    return name && dayNames[name]
        ? `Nama hari Bali; sama dengan hari ${dayNames[name]}.`
        : 'Nama hari Bali dalam sepekan.';
}

const styles = StyleSheet.create({
    activity: {
        backgroundColor: colors.surface,
        borderColor: colors.border,
        borderRadius: radius.lg,
        borderWidth: 1,
        flexBasis: '47%',
        flexGrow: 1,
        gap: spacing.sm,
        minHeight: 104,
        padding: spacing.lg,
    },
    activityIcon: {
        alignSelf: 'flex-start',
        backgroundColor: colors.primarySoft,
        borderRadius: 18,
        overflow: 'hidden',
        padding: spacing.sm,
    },
    activityGrid: {
        flexDirection: 'row',
        flexWrap: 'wrap',
        gap: spacing.md,
    },
    card: { gap: spacing.md },
    calendarItem: { gap: spacing.xs },
    dailyCard: { gap: spacing.lg },
    details: { marginTop: spacing.xl },
    divider: { backgroundColor: colors.border, height: 1 },
    footer: {
        gap: spacing.sm,
        marginBottom: spacing.xl,
        marginTop: spacing.xxl,
    },
    header: {
        gap: spacing.xs,
        marginBottom: spacing.xl,
        marginTop: spacing.sm,
    },
    rahinaList: {
        flexDirection: 'row',
        flexWrap: 'wrap',
        gap: spacing.sm,
        marginTop: spacing.sm,
    },
    recommendationGroup: { gap: spacing.sm },
    recommendationList: {
        flexDirection: 'row',
        flexWrap: 'wrap',
        gap: spacing.sm,
    },
    recommendationPill: {
        borderRadius: 16,
        paddingHorizontal: spacing.md,
        paddingVertical: spacing.sm,
    },
    goodPill: { backgroundColor: colors.successSoft },
    avoidPill: { backgroundColor: colors.dangerSoft },
    pressed: { opacity: 0.65 },
    row: {
        alignItems: 'center',
        flexDirection: 'row',
        gap: spacing.md,
        justifyContent: 'space-between',
    },
    searchCard: {
        backgroundColor: colors.primary,
        borderRadius: radius.xl,
        gap: spacing.md,
        padding: spacing.xl,
    },
    sectionTitle: {
        gap: spacing.xs,
        marginBottom: spacing.md,
        marginTop: spacing.xxl,
    },
});
