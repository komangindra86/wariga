import { StyleSheet, View } from 'react-native';

import { AppText } from './AppText';

import { colors, radius, spacing } from '@/theme';

type Tone = 'neutral' | 'success' | 'warning' | 'danger' | 'info';
const tones = {
    neutral: { bg: colors.surfaceMuted, text: 'textMuted' },
    success: { bg: colors.successSoft, text: 'success' },
    warning: { bg: colors.warningSoft, text: 'warning' },
    danger: { bg: colors.dangerSoft, text: 'danger' },
    info: { bg: colors.infoSoft, text: 'info' },
} as const;

export function AppBadge({
    label,
    tone = 'neutral',
}: {
    label: string;
    tone?: Tone;
}) {
    const palette = tones[tone];
    return (
        <View style={[styles.badge, { backgroundColor: palette.bg }]}>
            <AppText color={palette.text} variant="caption" weight="semibold">
                {label}
            </AppText>
        </View>
    );
}

const styles = StyleSheet.create({
    badge: {
        alignSelf: 'flex-start',
        borderRadius: radius.full,
        paddingHorizontal: spacing.md,
        paddingVertical: 6,
    },
});
