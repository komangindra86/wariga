import type { BottomTabScreenProps } from '@react-navigation/bottom-tabs';
import type { CompositeScreenProps } from '@react-navigation/native';
import type { NativeStackScreenProps } from '@react-navigation/native-stack';
import { useQuery } from '@tanstack/react-query';
import { format, parseISO } from 'date-fns';
import { id } from 'date-fns/locale';
import { useEffect, useMemo, useState } from 'react';
import { Pressable, RefreshControl, StyleSheet, View } from 'react-native';

import { endpoints } from '@/api/endpoints';
import { queryKeys } from '@/api/queryKeys';
import {
    AppBadge,
    AppButton,
    AppCard,
    AppEmptyState,
    AppErrorState,
    AppSkeleton,
    AppText,
    OfflineBanner,
    ScreenContainer,
} from '@/components';
import { tokenStorage } from '@/services/tokenStorage';
import { colors, spacing } from '@/theme';
import type { BaliCalendarDate, DailyRecommendations } from '@/types/domain';
import type { MainTabParamList, RootStackParamList } from '@/types/navigation';

type Props = CompositeScreenProps<
    BottomTabScreenProps<MainTabParamList, 'Home'>,
    NativeStackScreenProps<RootStackParamList>
>;

export function HomeScreen({ navigation }: Props) {
    const [hasToken, setHasToken] = useState(false);
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
        enabled: hasToken,
        queryFn: endpoints.savedDewasa,
        queryKey: queryKeys.saved.all,
    });

    useEffect(() => {
        tokenStorage
            .get()
            .then(token => setHasToken(Boolean(token)))
            .catch(() => setHasToken(false));
    }, []);

    const popularActivities = useMemo(
        () =>
            (activities.data ?? [])
                .filter(activity => activity.is_popular)
                .slice(0, 4),
        [activities.data],
    );
    const isRefreshing =
        calendar.isRefetching ||
        daily.isRefetching ||
        activities.isRefetching ||
        (hasToken && saved.isRefetching);
    const refresh = () => {
        calendar.refetch().catch(() => undefined);
        daily.refetch().catch(() => undefined);
        activities.refetch().catch(() => undefined);
        if (hasToken) saved.refetch().catch(() => undefined);
    };

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
                <View style={styles.hero}>
                    <AppBadge label="Wariga Bali" tone="warning" />
                    <AppText
                        color="textInverse"
                        variant="display"
                        weight="bold"
                    >
                        Wariga Bali Santih
                    </AppText>
                    <AppText color="secondarySoft">
                        {format(new Date(), 'EEEE, d MMMM yyyy', {
                            locale: id,
                        })}
                    </AppText>
                </View>

                <SectionTitle title="Kalender Bali hari ini" />
                {calendar.isPending ? (
                    <CalendarSkeleton />
                ) : calendar.isError ? (
                    <AppCard>
                        <AppErrorState
                            message={calendar.error.message}
                            onRetry={() => {
                                calendar.refetch().catch(() => undefined);
                            }}
                        />
                    </AppCard>
                ) : (
                    <TodayCard calendar={calendar.data} />
                )}

                <SectionTitle title="Hari ini bagus untuk apa?" />
                {daily.isPending ? (
                    <DailySkeleton />
                ) : daily.isError ? (
                    <AppCard>
                        <AppErrorState
                            message={daily.error.message}
                            onRetry={() => daily.refetch().catch(() => undefined)}
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

                <View style={styles.actions}>
                    <AppButton
                        fullWidth
                        label="Cari Dewasa Ayu"
                        onPress={() => navigation.navigate('ActivityList')}
                    />
                    <AppButton
                        fullWidth
                        label="Lihat Kalender"
                        onPress={() => navigation.navigate('Calendar')}
                        variant="outline"
                    />
                </View>

                <SectionTitle title="Kegiatan populer" />
                {activities.isPending ? (
                    <AppSkeleton height={92} />
                ) : activities.isError ? (
                    <AppErrorState
                        message={activities.error.message}
                        onRetry={() => {
                            activities.refetch().catch(() => undefined);
                        }}
                    />
                ) : popularActivities.length === 0 ? (
                    <AppEmptyState
                        actionLabel="Lihat semua kegiatan"
                        message="Belum ada kegiatan populer yang ditandai."
                        onAction={() => navigation.navigate('ActivityList')}
                        title="Kegiatan belum tersedia"
                    />
                ) : (
                    <View style={styles.activityGrid}>
                        {popularActivities.map(activity => (
                            <AppCard key={activity.id} style={styles.activity}>
                                <AppText style={styles.activityIcon}>
                                    {activity.icon ?? '◇'}
                                </AppText>
                                <AppText weight="semibold">
                                    {activity.name}
                                </AppText>
                                {activity.category ? (
                                    <AppText
                                        color="textMuted"
                                        variant="caption"
                                    >
                                        {activity.category}
                                    </AppText>
                                ) : null}
                            </AppCard>
                        ))}
                    </View>
                )}

                <SectionTitle title="Terakhir disimpan" />
                {!hasToken ? (
                    <AppEmptyState
                        actionLabel="Masuk"
                        message="Masuk untuk melihat hasil dewasa ayu yang pernah Anda simpan."
                        onAction={() => navigation.navigate('Login')}
                        title="Belum masuk"
                    />
                ) : saved.isPending ? (
                    <AppSkeleton height={96} />
                ) : saved.isError ? (
                    <AppErrorState
                        message={saved.error.message}
                        onRetry={() => {
                            saved.refetch().catch(() => undefined);
                        }}
                    />
                ) : !saved.data?.[0] ? (
                    <AppEmptyState
                        actionLabel="Cari Dewasa Ayu"
                        message="Hasil yang Anda simpan akan muncul di sini."
                        onAction={() => navigation.navigate('ActivityList')}
                        title="Belum ada hasil tersimpan"
                    />
                ) : (
                    <AppCard style={styles.card}>
                        <AppText weight="semibold">
                            {saved.data[0].activity?.name ?? 'Hasil Dewasa Ayu'}
                        </AppText>
                        <AppText color="textMuted" variant="bodySmall">
                            {format(
                                parseISO(saved.data[0].date),
                                'd MMMM yyyy',
                                {
                                    locale: id,
                                },
                            )}
                        </AppText>
                        <AppButton
                            label="Lihat hasil"
                            onPress={() =>
                                navigation.navigate('SavedDetail', {
                                    savedId: saved.data![0].id,
                                })
                            }
                            variant="ghost"
                        />
                    </AppCard>
                )}

                <AppCard style={styles.infoCard}>
                    <AppText variant="titleSmall" weight="semibold">
                        Tentang Wariga
                    </AppText>
                    <AppText color="textMuted" variant="bodySmall">
                        Wariga adalah pengetahuan tradisional Bali untuk
                        memahami siklus waktu dan mempertimbangkan hari yang
                        selaras bagi suatu kegiatan.
                    </AppText>
                    <AppText color="textMuted" variant="caption">
                        Hasil merupakan panduan budaya, bukan keputusan mutlak.
                        Pertimbangkan pula keadaan, adat setempat, dan nasihat
                        pihak yang dipercaya.
                    </AppText>
                    <AppButton
                        label="Baca disclaimer"
                        onPress={() => navigation.navigate('Disclaimer')}
                        variant="ghost"
                    />
                </AppCard>
            </ScreenContainer>
        </>
    );
}

function SectionTitle({ title }: { title: string }) {
    return (
        <AppText style={styles.sectionTitle} variant="title" weight="semibold">
            {title}
        </AppText>
    );
}

function CalendarSkeleton() {
    return (
        <AppCard style={styles.card}>
            <AppSkeleton height={24} width="55%" />
            <AppSkeleton />
            <AppSkeleton />
            <AppSkeleton width="70%" />
        </AppCard>
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
    const basis = [
        data.calendar_basis.saptawara,
        data.calendar_basis.pancawara,
        data.calendar_basis.wuku,
        data.calendar_basis.sasih,
    ].filter(Boolean).join(' • ');

    return (
        <AppCard style={styles.dailyCard}>
            <View style={styles.dailyHeader}>
                <View style={styles.dailyHeading}>
                    <AppText variant="titleSmall" weight="semibold">
                        Rekomendasi hari ini
                    </AppText>
                    {basis ? (
                        <AppText color="textMuted" variant="caption">
                            Berdasarkan {basis}
                        </AppText>
                    ) : null}
                </View>
                <AppBadge label="Otomatis" tone="success" />
            </View>

            <RecommendationGroup
                empty="Belum ada kegiatan dengan dukungan kuat hari ini."
                items={data.recommended}
                label="Baik dilakukan"
                onSelect={slug => onSelect(slug, data.date)}
                tone="good"
            />
            <View style={styles.divider} />
            <RecommendationGroup
                empty="Tidak ada pantangan kuat yang cocok hari ini."
                items={data.avoid}
                label="Sebaiknya dihindari"
                onSelect={slug => onSelect(slug, data.date)}
                tone="avoid"
            />
            <AppText color="textMuted" variant="caption">
                Ketuk kegiatan untuk melihat alasan dan hal yang perlu dipertimbangkan.
            </AppText>
        </AppCard>
    );
}

function RecommendationGroup({ items, label, empty, tone, onSelect }: {
    items: DailyRecommendations['recommended'];
    label: string;
    empty: string;
    tone: 'good' | 'avoid';
    onSelect: (slug: string) => void;
}) {
    return (
        <View style={styles.recommendationGroup}>
            <AppText color={tone === 'good' ? 'success' : 'danger'} weight="semibold">
                {tone === 'good' ? '✓' : '–'} {label}
            </AppText>
            {items.length === 0 ? (
                <AppText color="textMuted" variant="bodySmall">{empty}</AppText>
            ) : (
                <View style={styles.recommendationList}>
                    {items.map(item => (
                        <Pressable
                            key={item.id}
                            onPress={() => onSelect(item.slug)}
                            style={({ pressed }) => [
                                styles.recommendationPill,
                                tone === 'good' ? styles.goodPill : styles.avoidPill,
                                pressed && styles.pressed,
                            ]}
                        >
                            <AppText variant="bodySmall" weight="semibold">
                                {item.icon ? `${item.icon} ` : ''}{item.name}
                            </AppText>
                        </Pressable>
                    ))}
                </View>
            )}
        </View>
    );
}

function TodayCard({ calendar }: { calendar: BaliCalendarDate }) {
    const phase = calendar.penanggal
        ? `Penanggal ${calendar.penanggal}`
        : calendar.pangelong
        ? `Pangelong ${calendar.pangelong}`
        : null;
    const moon = calendar.is_full_moon
        ? 'Purnama'
        : calendar.is_new_moon
        ? 'Tilem'
        : null;
    const verified = calendar.verification_status === 'verified';
    const rahina = calendar.observances?.map(item => item.name).join(', ');

    return (
        <AppCard style={styles.card}>
            <View style={styles.cardHeader}>
                <AppText variant="titleSmall" weight="semibold">
                    {format(parseISO(calendar.date), 'd MMMM yyyy', {
                        locale: id,
                    })}
                </AppText>
                <AppBadge
                    label={verified ? 'Terverifikasi' : 'Belum terverifikasi'}
                    tone={verified ? 'success' : 'warning'}
                />
            </View>
            <CalendarRow label="Wuku" meaning={cycleMeaning('pekan dalam siklus 30 wuku', calendar.wuku_order, 30)} value={calendar.wuku} />
            <CalendarRow label="Sapta Wara" meaning={waraMeaning(calendar.saptawara, calendar.saptawara_order, 7)} value={calendar.saptawara} />
            <CalendarRow label="Panca Wara" meaning={waraMeaning(calendar.pancawara, calendar.pancawara_order, 5)} value={calendar.pancawara} />
            <CalendarRow label="Tri Wara" meaning={waraMeaning(calendar.triwara, calendar.triwara_order, 3)} value={calendar.triwara} />
            <CalendarRow label="Sad Wara" meaning={waraMeaning(calendar.sadwara, calendar.sadwara_order, 6)} value={calendar.sadwara} />
            {calendar.lunar_available ? (
                <>
                    <CalendarRow
                        label="Penanggal/Panglong"
                        meaning={calendar.penanggal ? 'Hari setelah Tilem menuju Purnama.' : 'Hari setelah Purnama menuju Tilem.'}
                        value={phase}
                    />
                    <CalendarRow label="Sasih" meaning="Bulan dalam kalender lunar Bali." value={calendar.sasih} />
                    {moon ? (
                        <CalendarRow label="Hari khusus" meaning="Titik utama dalam siklus bulan Bali." value={moon} />
                    ) : null}
                </>
            ) : (
                <AppText color="textMuted" variant="bodySmall">
                    {calendar.lunar_message ??
                        'Data sasih dan fase bulan belum tersedia.'}
                </AppText>
            )}
            <CalendarRow
                label="Rahina"
                meaning={rahina ? 'Hari suci atau peringatan yang jatuh pada tanggal ini.' : 'Tidak ada hari suci khusus yang terdeteksi.'}
                value={rahina || 'Tidak ada rahina khusus'}
            />
        </AppCard>
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
                <AppText color="textMuted" variant="bodySmall">{label}</AppText>
                <AppText variant="bodySmall" weight="semibold">{value ?? 'Belum tersedia'}</AppText>
            </View>
            {meaning ? <AppText color="textMuted" variant="caption">{meaning}</AppText> : null}
        </View>
    );
}

function cycleMeaning(label: string, order?: number | null, total?: number) {
    const description = `${label.charAt(0).toUpperCase()}${label.slice(1)}`;
    return order ? `${description}; urutan ke-${order} dari ${total}.` : `${description}.`;
}

function waraMeaning(name?: string | null, order?: number | null, total?: number) {
    const dayNames: Record<string, string> = {
        Redite: 'Minggu', Soma: 'Senin', Anggara: 'Selasa', Buda: 'Rabu',
        Wraspati: 'Kamis', Sukra: 'Jumat', Saniscara: 'Sabtu',
    };
    const position = order ? `urutan ke-${order} dalam siklus ${total} hari` : `bagian dari siklus ${total} hari`;
    return name && dayNames[name] ? `${name} berarti hari ${dayNames[name]}; ${position}.` : `${name ?? 'Unsur ini'} adalah ${position}.`;
}

const styles = StyleSheet.create({
    actions: {
        gap: spacing.md,
        marginBottom: spacing.xl,
        marginTop: spacing.xl,
    },
    activity: {
        flexBasis: '47%',
        flexGrow: 1,
        gap: spacing.sm,
        minHeight: 132,
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
        marginBottom: spacing.xl,
    },
    card: { gap: spacing.md },
    calendarItem: { gap: spacing.xs },
    dailyCard: { gap: spacing.lg },
    dailyHeader: {
        alignItems: 'flex-start',
        flexDirection: 'row',
        gap: spacing.md,
        justifyContent: 'space-between',
    },
    dailyHeading: { flex: 1, gap: spacing.xs },
    divider: { backgroundColor: colors.border, height: 1 },
    recommendationGroup: { gap: spacing.sm },
    recommendationList: { flexDirection: 'row', flexWrap: 'wrap', gap: spacing.sm },
    recommendationPill: { borderRadius: 16, paddingHorizontal: spacing.md, paddingVertical: spacing.sm },
    goodPill: { backgroundColor: colors.successSoft },
    avoidPill: { backgroundColor: colors.dangerSoft },
    pressed: { opacity: 0.65 },
    cardHeader: {
        alignItems: 'flex-start',
        flexDirection: 'row',
        flexWrap: 'wrap',
        gap: spacing.sm,
        justifyContent: 'space-between',
    },
    hero: {
        backgroundColor: colors.darkSurface,
        borderRadius: 28,
        gap: spacing.md,
        marginBottom: spacing.xl,
        marginTop: spacing.sm,
        overflow: 'hidden',
        padding: spacing.xl,
    },
    infoCard: {
        gap: spacing.md,
        marginBottom: spacing.xl,
        marginTop: spacing.xl,
    },
    row: {
        alignItems: 'center',
        flexDirection: 'row',
        gap: spacing.md,
        justifyContent: 'space-between',
    },
    sectionTitle: { marginBottom: spacing.md, marginTop: spacing.xxl },
});
