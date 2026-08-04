import { ActivityIndicator, Pressable, StyleSheet, View } from 'react-native';

import { AppText } from '@/components/AppText';
import { colors, radius, screen, spacing } from '@/theme';

type Props = {
    label: string;
    onPress: () => void;
    variant?: 'primary' | 'secondary' | 'outline' | 'ghost';
    disabled?: boolean;
    loading?: boolean;
    fullWidth?: boolean;
};

const variants = {
    primary: {
        backgroundColor: colors.primary,
        borderColor: colors.primary,
        text: 'textInverse',
    },
    secondary: {
        backgroundColor: colors.secondary,
        borderColor: colors.secondary,
        text: 'text',
    },
    outline: {
        backgroundColor: colors.transparent,
        borderColor: colors.primary,
        text: 'primary',
    },
    ghost: {
        backgroundColor: colors.transparent,
        borderColor: colors.transparent,
        text: 'primary',
    },
} as const;

export function AppButton({
    disabled = false,
    fullWidth = false,
    label,
    loading = false,
    onPress,
    variant = 'primary',
}: Props) {
    const palette = variants[variant];
    const inactive = disabled || loading;
    return (
        <Pressable
            accessibilityRole="button"
            disabled={inactive}
            onPress={onPress}
            style={({ pressed }) => [
                styles.button,
                palette,
                fullWidth && styles.fullWidth,
                pressed && styles.pressed,
                inactive && styles.disabled,
            ]}
        >
            <View style={styles.content}>
                {loading ? (
                    <ActivityIndicator color={colors[palette.text]} />
                ) : null}
                <AppText color={palette.text} weight="semibold">
                    {label}
                </AppText>
            </View>
        </Pressable>
    );
}

const styles = StyleSheet.create({
    button: {
        alignItems: 'center',
        alignSelf: 'flex-start',
        borderRadius: radius.full,
        borderWidth: 1,
        justifyContent: 'center',
        minHeight: screen.minTouchTarget,
        paddingHorizontal: spacing.xl,
    },
    content: {
        alignItems: 'center',
        flexDirection: 'row',
        gap: spacing.sm,
        paddingVertical: spacing.xs,
    },
    disabled: { opacity: 0.5 },
    fullWidth: { alignSelf: 'stretch' },
    pressed: { opacity: 0.82 },
});
