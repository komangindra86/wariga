import { StyleSheet, View } from 'react-native';

import { AppButton } from './AppButton';
import { AppText } from './AppText';

import { spacing } from '@/theme';

export function AppErrorState({
    message = 'Terjadi kesalahan. Silakan coba lagi.',
    onRetry,
}: {
    message?: string;
    onRetry?: () => void;
}) {
    return (
        <View style={styles.container}>
            <AppText variant="titleSmall" weight="semibold">
                Belum berhasil
            </AppText>
            <AppText color="textMuted" style={styles.center}>
                {message}
            </AppText>
            {onRetry ? (
                <AppButton
                    label="Coba lagi"
                    onPress={onRetry}
                    variant="outline"
                />
            ) : null}
        </View>
    );
}

const styles = StyleSheet.create({
    center: { textAlign: 'center' },
    container: { alignItems: 'center', gap: spacing.md, padding: spacing.xl },
});
