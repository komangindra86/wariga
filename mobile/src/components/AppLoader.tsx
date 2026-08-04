import { ActivityIndicator, StyleSheet, View } from 'react-native';

import { AppText } from './AppText';

import { colors, spacing } from '@/theme';

export function AppLoader({ label = 'Memuat...' }: { label?: string }) {
    return (
        <View accessibilityRole="progressbar" style={styles.container}>
            <ActivityIndicator color={colors.primary} size="large" />
            <AppText color="textMuted" variant="bodySmall">
                {label}
            </AppText>
        </View>
    );
}

const styles = StyleSheet.create({
    container: {
        alignItems: 'center',
        gap: spacing.sm,
        justifyContent: 'center',
        padding: spacing.xl,
    },
});
