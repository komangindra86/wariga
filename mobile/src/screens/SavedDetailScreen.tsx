import type { NativeStackScreenProps } from '@react-navigation/native-stack';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useEffect, useState } from 'react';
import { Alert, Share, StyleSheet, View } from 'react-native';

import { endpoints } from '@/api/endpoints';
import { queryKeys } from '@/api/queryKeys';
import {
    AppBadge,
    AppButton,
    AppCard,
    AppErrorState,
    AppSkeleton,
    AppText,
    ScreenContainer,
} from '@/components';
import {
    cancelSavedReminder,
    isReminderScheduled,
    loadReminderSettings,
    scheduleSavedReminder,
} from '@/services/reminderService';
import { spacing } from '@/theme';
import type { RootStackParamList } from '@/types/navigation';
import {
    buildSavedShareMessage,
    savedDateLabel,
    savedRecommendation,
    savedStatusLabel,
} from '@/utils/savedDewasa';

type Props = NativeStackScreenProps<RootStackParamList, 'SavedDetail'>;

export function SavedDetailScreen({ navigation, route }: Props) {
    const queryClient = useQueryClient();
    const [reminderActive, setReminderActive] = useState(false);
    const [reminderBusy, setReminderBusy] = useState(false);
    useEffect(() => {
        isReminderScheduled(route.params.savedId).then(setReminderActive);
    }, [route.params.savedId]);
    const saved = useQuery({
        queryFn: () => endpoints.savedDewasaDetail(route.params.savedId),
        queryKey: queryKeys.saved.detail(route.params.savedId),
    });
    const remove = useMutation({
        mutationFn: () => endpoints.deleteSavedDewasa(route.params.savedId),
        onSuccess: async () => {
            await cancelSavedReminder(route.params.savedId);
            await queryClient.invalidateQueries({ queryKey: queryKeys.saved.all });
            navigation.goBack();
        },
    });
    const share = useMutation({
        mutationFn: () => endpoints.shareSavedDewasa(route.params.savedId),
        onSuccess: async link => {
            if (saved.data) {
                await Share.share({
                    message: buildSavedShareMessage(saved.data, link.url),
                    title: 'Rekomendasi Dewasa Ayu',
                });
            }
        },
        onError: error =>
            Alert.alert('Bagikan gagal', error.message),
    });

    if (saved.isPending) {
        return (
            <ScreenContainer style={styles.content}>
                <AppSkeleton height={36} width="70%" />
                <AppSkeleton height={180} />
                <AppSkeleton height={110} />
            </ScreenContainer>
        );
    }
    if (saved.isError) {
        return (
            <ScreenContainer>
                <AppErrorState
                    message={saved.error.message}
                    onRetry={() => saved.refetch().catch(() => undefined)}
                />
            </ScreenContainer>
        );
    }

    const item = saved.data;
    const recommendation = savedRecommendation(item);
    const recalendar = recommendation?.calendar;

    const confirmDelete = () =>
        Alert.alert(
            'Hapus hasil tersimpan?',
            'Hasil ini akan dihapus dari akun Anda.',
            [
                { style: 'cancel', text: 'Batal' },
                {
                    onPress: () => remove.mutate(),
                    style: 'destructive',
                    text: 'Hapus',
                },
            ],
        );

    const toggleReminder = async () => {
        setReminderBusy(true);
        try {
            if (reminderActive) {
                await cancelSavedReminder(item.id);
                setReminderActive(false);
                return;
            }
            const settings = await loadReminderSettings();
            if (!settings.enabled) {
                Alert.alert(
                    'Pengingat belum aktif',
                    'Aktifkan dan simpan pengaturan pengingat terlebih dahulu.',
                    [
                        { text: 'Batal', style: 'cancel' },
                        {
                            text: 'Buka pengaturan',
                            onPress: () => navigation.navigate('ReminderSettings'),
                        },
                    ],
                );
                return;
            }
            await scheduleSavedReminder({
                activityName: item.activity?.name ?? 'kegiatan tersimpan',
                date: item.date,
                savedId: item.id,
            });
            setReminderActive(true);
            Alert.alert('Pengingat aktif', 'Notifikasi lokal telah dijadwalkan.');
        } catch (error) {
            Alert.alert(
                'Pengingat tidak dapat dijadwalkan',
                error instanceof Error && error.message === 'NO_FUTURE_REMINDER'
                    ? 'Waktu pengingat untuk tanggal ini sudah lewat.'
                    : 'Silakan coba lagi.',
            );
        } finally {
            setReminderBusy(false);
        }
    };

    return (
        <ScreenContainer style={styles.content}>
            <AppText variant="title" weight="bold">
                {item.activity?.name ?? 'Hasil tersimpan'}
            </AppText>
            <AppText color="textMuted">{savedDateLabel(item.date)}</AppText>
            <AppCard style={styles.card}>
                <View style={styles.summary}>
                    <View>
                        <AppText color="textMuted" variant="caption">Skor snapshot</AppText>
                        <AppText color="primary" variant="display" weight="bold">
                            {item.score}
                        </AppText>
                    </View>
                    <AppBadge
                        label={savedStatusLabel(item)}
                        tone={
                            item.status === 'avoid'
                                ? 'danger'
                                : item.status === 'consideration'
                                ? 'warning'
                                : 'success'
                        }
                    />
                </View>
                <DetailRow label="Sapta Wara" value={recalendar?.wewaran?.saptawara?.nama} />
                <DetailRow label="Panca Wara" value={recalendar?.wewaran?.pancawara?.nama} />
                <DetailRow label="Wuku" value={recalendar?.wuku?.nama} />
                <DetailRow label="Engine version" value={item.engine_version} />
                <DetailRow label="Disimpan" value={savedDateLabel(item.created_at.slice(0, 10))} />
            </AppCard>
            {item.notes ? (
                <AppCard style={styles.card}>
                    <AppText weight="semibold">Catatan</AppText>
                    <AppText>{item.notes}</AppText>
                </AppCard>
            ) : null}
            {item.snapshot_outdated ? (
                <AppCard style={styles.notice}>
                    <AppText color="danger" weight="semibold">
                        {item.snapshot_notice}
                    </AppText>
                </AppCard>
            ) : null}
            <AppButton
                disabled={!item.activity}
                fullWidth
                label="Lihat hasil tersimpan"
                onPress={() =>
                    item.activity &&
                    navigation.navigate('DewasaDetail', {
                        activity: item.activity.slug,
                        date: item.date,
                    })
                }
                variant="outline"
            />
            <AppButton
                disabled={!item.activity}
                fullWidth
                label="Hitung ulang"
                onPress={() =>
                    item.activity &&
                    navigation.navigate('DewasaDetail', {
                        activity: item.activity.slug,
                        date: item.date,
                    })
                }
            />
            <AppButton
                fullWidth
                label={reminderActive ? 'Batalkan pengingat' : 'Aktifkan pengingat'}
                loading={reminderBusy}
                onPress={() => toggleReminder()}
                variant="outline"
            />
            <AppButton
                fullWidth
                label="Bagikan"
                loading={share.isPending}
                onPress={() => share.mutate()}
                variant="secondary"
            />
            <AppButton
                fullWidth
                label="Hapus simpanan"
                loading={remove.isPending}
                onPress={confirmDelete}
                variant="ghost"
            />
        </ScreenContainer>
    );
}

function DetailRow({ label, value }: { label: string; value?: string }) {
    return (
        <View style={styles.row}>
            <AppText color="textMuted" variant="bodySmall">{label}</AppText>
            <AppText variant="bodySmall" weight="semibold">{value ?? 'Tidak tersedia'}</AppText>
        </View>
    );
}

const styles = StyleSheet.create({
    card: { gap: spacing.md },
    content: { gap: spacing.md },
    notice: { gap: spacing.sm },
    row: { flexDirection: 'row', justifyContent: 'space-between', gap: spacing.md },
    summary: {
        alignItems: 'center',
        flexDirection: 'row',
        justifyContent: 'space-between',
    },
});
