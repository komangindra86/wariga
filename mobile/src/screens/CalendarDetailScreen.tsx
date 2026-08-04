import type { NativeStackScreenProps } from '@react-navigation/native-stack';
import { useQuery } from '@tanstack/react-query';
import { format, parseISO } from 'date-fns';
import { id } from 'date-fns/locale';
import { StyleSheet, View } from 'react-native';

import { endpoints } from '@/api/endpoints';
import { queryKeys } from '@/api/queryKeys';
import {
    AppBadge,
    AppButton,
    AppCard,
    AppErrorState,
    AppSkeleton,
    AppText,
    OfflineBanner,
    ScreenContainer,
} from '@/components';
import { colors, spacing } from '@/theme';
import type { RootStackParamList } from '@/types/navigation';
import { importantDay } from '@/utils/calendar';

type Props = NativeStackScreenProps<RootStackParamList, 'CalendarDetail'>;

export function CalendarDetailScreen({ navigation, route }: Props) {
    const { date } = route.params;
    const query = useQuery({
        queryFn: () => endpoints.calendarDate(date),
        queryKey: queryKeys.calendar.date(date),
        staleTime: 1000 * 60 * 60,
    });

    return (
        <>
            <OfflineBanner visible={query.fetchStatus === 'paused'} />
            <ScreenContainer style={styles.container}>
                <AppText color="textMuted">Rincian Kalender Bali</AppText>
                <AppText variant="title" weight="bold">
                    {format(parseISO(date), 'EEEE, d MMMM yyyy', {
                        locale: id,
                    })}
                </AppText>
                {query.isPending ? (
                    <AppCard style={styles.card}>
                        <AppSkeleton height={24} />
                        <AppSkeleton />
                        <AppSkeleton />
                        <AppSkeleton />
                    </AppCard>
                ) : query.isError ? (
                    <AppErrorState
                        message={query.error.message}
                        onRetry={() => query.refetch()}
                    />
                ) : (
                    <AppCard style={styles.card}>
                        <View style={styles.badges}>
                            <AppBadge
                                label={
                                    query.data.verification_status ===
                                    'verified'
                                        ? 'Terverifikasi'
                                        : 'Data terbatas'
                                }
                                tone={
                                    query.data.verification_status ===
                                    'verified'
                                        ? 'success'
                                        : 'warning'
                                }
                            />
                            {importantDay(query.data) ? (
                                <AppBadge
                                    label={importantDay(query.data)!}
                                    tone="warning"
                                />
                            ) : null}
                        </View>
                        <DetailRow
                            label="Sapta Wara"
                            value={query.data.saptawara}
                        />
                        <DetailRow
                            label="Panca Wara"
                            value={query.data.pancawara}
                        />
                        <DetailRow
                            label="Tri Wara"
                            value={query.data.triwara}
                        />
                        <DetailRow label="Wuku" value={query.data.wuku} />
                        <DetailRow label="Sasih" value={query.data.sasih} />
                        <DetailRow
                            label="Fase"
                            value={
                                query.data.penanggal
                                    ? `Penanggal ${query.data.penanggal}`
                                    : query.data.pangelong
                                    ? `Pangelong ${query.data.pangelong}`
                                    : null
                            }
                        />
                        {!query.data.lunar_available ? (
                            <AppText color="textMuted" variant="bodySmall">
                                {query.data.lunar_message ??
                                    'Data lunar belum tersedia untuk tanggal ini.'}
                            </AppText>
                        ) : null}
                        {(query.data.observances?.length ?? 0) > 0 ? (
                            <View style={styles.observances}>
                                <AppText weight="semibold">
                                    Rahina dan peringatan
                                </AppText>
                                {query.data.observances?.map(observance => (
                                    <View
                                        key={observance.name}
                                        style={styles.observance}
                                    >
                                        <View
                                            style={[
                                                styles.observanceDot,
                                                observance.name === 'Purnama'
                                                    ? styles.purnamaDot
                                                    : observance.name ===
                                                      'Tilem'
                                                    ? styles.tilemDot
                                                    : styles.rahinaDot,
                                            ]}
                                        />
                                        <AppText variant="bodySmall">
                                            {observance.name}
                                        </AppText>
                                    </View>
                                ))}
                            </View>
                        ) : null}
                    </AppCard>
                )}
                <AppButton
                    label="Coba tanggal ini untuk Dewasa Ayu"
                    onPress={() => navigation.navigate('ActivityList')}
                    variant="outline"
                />
            </ScreenContainer>
        </>
    );
}

function DetailRow({ label, value }: { label: string; value?: string | null }) {
    return (
        <View style={styles.row}>
            <AppText color="textMuted">{label}</AppText>
            <AppText weight="semibold">{value ?? 'Belum tersedia'}</AppText>
        </View>
    );
}

const styles = StyleSheet.create({
    badges: { flexDirection: 'row', flexWrap: 'wrap', gap: spacing.sm },
    card: { gap: spacing.md },
    container: { gap: spacing.lg },
    observance: {
        alignItems: 'center',
        flexDirection: 'row',
        gap: spacing.sm,
    },
    observanceDot: { borderRadius: 5, height: 10, width: 10 },
    observances: { gap: spacing.sm, marginTop: spacing.sm },
    purnamaDot: { backgroundColor: colors.danger },
    rahinaDot: { backgroundColor: colors.warning },
    row: {
        flexDirection: 'row',
        gap: spacing.md,
        justifyContent: 'space-between',
    },
    tilemDot: { backgroundColor: colors.text },
});
