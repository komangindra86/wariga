import { StyleSheet, View } from 'react-native';

import { colors, spacing } from '@/theme';

export function AppDivider({ inset = false }: { inset?: boolean }) {
    return <View style={[styles.divider, inset ? styles.inset : null]} />;
}

const styles = StyleSheet.create({
    divider: {
        backgroundColor: colors.border,
        height: StyleSheet.hairlineWidth,
    },
    inset: { marginHorizontal: spacing.lg },
});
