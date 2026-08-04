import type { NativeStackScreenProps } from '@react-navigation/native-stack';
import { Alert, StyleSheet } from 'react-native';

import { AppButton, AppCard, AppLoader, AppText, ScreenContainer } from '@/components';
import { useAuthStore } from '@/store/authStore';
import { spacing } from '@/theme';
import type { RootStackParamList } from '@/types/navigation';

type Props = NativeStackScreenProps<RootStackParamList, 'Profile'>;

export function ProfileScreen({ navigation }: Props) {
    const { user, isRestoring, logout, deleteAccount } = useAuthStore();
    if (isRestoring) return <AppLoader label="Memulihkan sesi..." />;
    if (!user) {
        return (
            <ScreenContainer style={styles.container}>
                <AppText variant="title" weight="bold">Akun</AppText>
                <AppText color="textMuted">Anda sedang menggunakan Wariga sebagai tamu.</AppText>
                <AppButton fullWidth label="Masuk" onPress={() => navigation.replace('Login')} />
                <AppButton fullWidth label="Daftar" onPress={() => navigation.replace('Register')} variant="outline" />
            </ScreenContainer>
        );
    }
    const confirmDelete = () => Alert.alert(
        'Hapus akun?',
        'Akun dan seluruh data terkait akan dihapus permanen. Tindakan ini tidak dapat dibatalkan.',
        [
            { text: 'Batal', style: 'cancel' },
            {
                text: 'Hapus permanen',
                style: 'destructive',
                onPress: async () => {
                    try {
                        await deleteAccount();
                        navigation.popToTop();
                    } catch {
                        Alert.alert('Gagal menghapus akun', 'Silakan coba lagi.');
                    }
                },
            },
        ],
    );
    return (
        <ScreenContainer style={styles.container}>
            <AppText variant="title" weight="bold">Profil</AppText>
            <AppCard style={styles.card}>
                <AppText color="textMuted" variant="caption">NAMA</AppText>
                <AppText weight="semibold">{user.name}</AppText>
                <AppText color="textMuted" variant="caption">EMAIL</AppText>
                <AppText>{user.email}</AppText>
            </AppCard>
            <AppButton fullWidth label="Pengaturan pengingat" onPress={() => navigation.navigate('ReminderSettings')} variant="outline" />
            <AppButton fullWidth label="Keluar" onPress={async () => { await logout(); navigation.popToTop(); }} variant="outline" />
            <AppButton fullWidth label="Hapus akun" onPress={confirmDelete} variant="ghost" />
        </ScreenContainer>
    );
}

const styles = StyleSheet.create({
    card: { gap: spacing.sm },
    container: { gap: spacing.lg },
});
