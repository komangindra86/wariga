import type { BottomTabScreenProps } from '@react-navigation/bottom-tabs';
import type { CompositeScreenProps } from '@react-navigation/native';
import type { NativeStackScreenProps } from '@react-navigation/native-stack';
import { useQuery } from '@tanstack/react-query';
import { Alert, Linking, StyleSheet, View } from 'react-native';

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
    ScreenContainer,
} from '@/components';
import { environment } from '@/config/environment';
import { useAuthStore } from '@/store/authStore';
import { colors, spacing } from '@/theme';
import type { SavedDewasa } from '@/types/domain';
import type { MainTabParamList, RootStackParamList } from '@/types/navigation';
import {
    savedDateLabel,
    savedRecommendation,
    savedStatusLabel,
} from '@/utils/savedDewasa';

type SavedProps = CompositeScreenProps<
    BottomTabScreenProps<MainTabParamList, 'Saved'>,
    NativeStackScreenProps<RootStackParamList>
>;
export function SavedScreen({ navigation }: SavedProps) {
    const isAuthenticated = useAuthStore(state => state.isAuthenticated);
    const saved = useQuery({
        enabled: isAuthenticated,
        queryFn: endpoints.savedDewasa,
        queryKey: queryKeys.saved.all,
    });

    if (!isAuthenticated) {
        return (
            <ScreenContainer style={styles.container}>
                <AppText variant="title" weight="bold">
                    Tersimpan
                </AppText>
                <AppEmptyState
                    actionLabel="Masuk ke akun"
                    message="Masuk untuk menyimpan dan membuka kembali rekomendasi Anda."
                    onAction={() => navigation.navigate('Login')}
                    title="Belum masuk"
                />
            </ScreenContainer>
        );
    }

    return (
        <ScreenContainer style={styles.container}>
            <AppText variant="title" weight="bold">
                Tersimpan
            </AppText>
            {saved.isPending ? (
                <View style={styles.list}>
                    <AppSkeleton height={170} />
                    <AppSkeleton height={170} />
                </View>
            ) : saved.isError ? (
                <AppErrorState
                    message={saved.error.message}
                    onRetry={() => saved.refetch().catch(() => undefined)}
                />
            ) : saved.data.length === 0 ? (
                <AppEmptyState
                    actionLabel="Cari hari baik"
                    message="Rekomendasi yang Anda simpan akan tampil di sini."
                    onAction={() => navigation.navigate('ActivityList')}
                    title="Belum ada hasil tersimpan"
                />
            ) : (
                <View style={styles.list}>
                    {saved.data.map(item => (
                        <SavedCard
                            key={item.id}
                            item={item}
                            onOpen={() =>
                                navigation.navigate('SavedDetail', {
                                    savedId: item.id,
                                })
                            }
                        />
                    ))}
                </View>
            )}
        </ScreenContainer>
    );
}

function SavedCard({
    item,
    onOpen,
}: {
    item: SavedDewasa;
    onOpen: () => void;
}) {
    const recommendation = savedRecommendation(item);
    return (
        <AppCard style={styles.savedCard}>
            <View style={styles.cardHeader}>
                <View style={styles.cardTitle}>
                    <AppText weight="bold">
                        {item.activity?.name ?? 'Kegiatan'}
                    </AppText>
                    <AppText color="textMuted" variant="bodySmall">
                        {savedDateLabel(item.date)}
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
            <AppText>Nilai saat disimpan: {item.score}/100</AppText>
            <AppText color="textMuted" variant="bodySmall">
                Disimpan {savedDateLabel(item.created_at.slice(0, 10))}
            </AppText>
            {item.notes ? (
                <AppText variant="bodySmall">Catatan: {item.notes}</AppText>
            ) : null}
            {item.snapshot_outdated ? (
                <AppText color="danger" variant="bodySmall" weight="semibold">
                    {item.snapshot_notice}
                </AppText>
            ) : null}
            <AppButton
                label={
                    recommendation
                        ? 'Lihat hasil tersimpan'
                        : 'Buka hasil tersimpan'
                }
                onPress={onOpen}
                variant="outline"
            />
        </AppCard>
    );
}

type AboutProps = CompositeScreenProps<
    BottomTabScreenProps<MainTabParamList, 'About'>,
    NativeStackScreenProps<RootStackParamList>
>;
export function AboutScreen({ navigation }: AboutProps) {
    return (
        <ScreenContainer style={styles.container}>
            <AppText variant="title" weight="bold">
                Lainnya
            </AppText>
            <AppCard style={styles.card}>
                <AppText weight="semibold">Wariga Bali Santih</AppText>
                <AppText color="textMuted">
                    Pendamping digital untuk memahami Kalender Bali, mencari
                    Dewasa Ayu, dan melihat alasan di balik setiap rekomendasi.
                </AppText>
                <AppText color="textMuted" variant="bodySmall">
                    Mesin menggunakan siklus Wariga dan Pengalantaka, basis
                    pengetahuan Ala-Ayuning Dewasa, data terverifikasi bila
                    tersedia, serta hasil perhitungan yang diberi status secara
                    terbuka.
                </AppText>
                <AppText color="textMuted" variant="bodySmall">
                    Sebagian hasil dibandingkan dengan Kalender Bali Digital
                    sebagai referensi eksternal. Aplikasi ini tidak berafiliasi
                    dengan KalenderBali.org.
                </AppText>
                <AppText color="textMuted" variant="caption">
                    Umpan balik pengguna membantu menemukan pola, tetapi tidak
                    pernah langsung mengubah aturan tanpa tinjauan validator.
                </AppText>
                <AppButton
                    label="Akun Saya"
                    onPress={() => navigation.navigate('Profile')}
                    variant="outline"
                />
                <AppButton
                    label="Kebijakan Privasi"
                    onPress={() => navigation.navigate('PrivacyPolicy')}
                    variant="ghost"
                />
                <AppButton
                    label="Disclaimer"
                    onPress={() => navigation.navigate('Disclaimer')}
                    variant="ghost"
                />
                <AppButton
                    label="Kontribusi & Saran"
                    onPress={() => navigation.navigate('Contribution')}
                    variant="outline"
                />
            </AppCard>
            <AppCard style={styles.card}>
                <AppText variant="titleSmall" weight="semibold">
                    Dukung Pengembangan
                </AppText>
                <AppText color="textMuted" variant="bodySmall">
                    Dukungan sukarela membantu pemeliharaan layanan, validasi
                    data, dan pengembangan fitur Wariga Bali Santih.
                </AppText>
                <AppText color="textMuted" variant="caption">
                    Donasi tidak memengaruhi skor, hasil rekomendasi, akses
                    fitur, maupun prioritas layanan.
                </AppText>
                <View style={styles.donationAccount}>
                    <AppText color="textMuted" variant="caption">
                        Bank Mandiri Taspen
                    </AppText>
                    <AppText selectable variant="titleSmall" weight="bold">
                        1000041131
                    </AppText>
                    <AppText variant="bodySmall" weight="semibold">
                        PT Bali Santih Digital
                    </AppText>
                    <AppText color="textMuted" variant="caption">
                        Nomor rekening dapat ditekan lama untuk disalin.
                    </AppText>
                </View>
                {environment.donationUrl ? (
                    <AppButton
                        label="Donasi Sekarang"
                        onPress={() =>
                            Linking.openURL(environment.donationUrl!).catch(
                                () =>
                                    Alert.alert(
                                        'Tautan tidak dapat dibuka',
                                        'Silakan coba kembali beberapa saat lagi.',
                                    ),
                            )
                        }
                    />
                ) : null}
            </AppCard>
        </ScreenContainer>
    );
}

const styles = StyleSheet.create({
    card: { gap: spacing.md },
    donationAccount: {
        backgroundColor: colors.secondarySoft,
        borderRadius: 16,
        gap: spacing.xs,
        padding: spacing.md,
    },
    cardHeader: {
        alignItems: 'flex-start',
        flexDirection: 'row',
        gap: spacing.md,
        justifyContent: 'space-between',
    },
    cardTitle: { flex: 1, gap: spacing.xs },
    container: { gap: spacing.lg },
    list: { gap: spacing.md },
    savedCard: { gap: spacing.md },
});
