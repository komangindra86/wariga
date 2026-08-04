import { Pressable, StyleSheet, View } from 'react-native';

import { AppText } from './AppText';

import { colors, screen, spacing } from '@/theme';

type Props = {
    title: string;
    subtitle?: string;
    onBack?: () => void;
    action?: React.ReactNode;
};

export function AppHeader({ action, onBack, subtitle, title }: Props) {
    return (
        <View style={styles.header}>
            {onBack ? (
                <Pressable
                    accessibilityLabel="Kembali"
                    accessibilityRole="button"
                    onPress={onBack}
                    style={styles.back}
                >
                    <AppText color="primary" variant="titleSmall">
                        ‹
                    </AppText>
                </Pressable>
            ) : null}
            <View style={styles.title}>
                <AppText variant="titleSmall" weight="semibold">
                    {title}
                </AppText>
                {subtitle ? (
                    <AppText color="textMuted" variant="caption">
                        {subtitle}
                    </AppText>
                ) : null}
            </View>
            {action}
        </View>
    );
}

const styles = StyleSheet.create({
    back: {
        alignItems: 'center',
        justifyContent: 'center',
        minHeight: screen.minTouchTarget,
        minWidth: screen.minTouchTarget,
    },
    header: {
        alignItems: 'center',
        backgroundColor: colors.background,
        borderBottomColor: colors.border,
        borderBottomWidth: StyleSheet.hairlineWidth,
        flexDirection: 'row',
        gap: spacing.sm,
        minHeight: 64,
        paddingHorizontal: spacing.xl,
    },
    title: { flex: 1 },
});
