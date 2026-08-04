import { useState, type PropsWithChildren } from 'react';
import { Pressable, StyleSheet, View } from 'react-native';

import { AppText } from './AppText';

import { colors, radius, screen, spacing } from '@/theme';

export function AppAccordion({
    children,
    initiallyOpen = false,
    title,
}: PropsWithChildren<{ title: string; initiallyOpen?: boolean }>) {
    const [open, setOpen] = useState(initiallyOpen);
    return (
        <View style={styles.container}>
            <Pressable
                accessibilityRole="button"
                accessibilityState={{ expanded: open }}
                onPress={() => setOpen(value => !value)}
                style={styles.trigger}
            >
                <AppText style={styles.label} weight="semibold">
                    {title}
                </AppText>
                <View style={styles.icon}>
                    <AppText color="primary" weight="bold">
                        {open ? '−' : '+'}
                    </AppText>
                </View>
            </Pressable>
            {open ? <View style={styles.content}>{children}</View> : null}
        </View>
    );
}

const styles = StyleSheet.create({
    container: {
        borderColor: colors.border,
        backgroundColor: colors.surface,
        borderRadius: radius.lg,
        borderWidth: StyleSheet.hairlineWidth,
        overflow: 'hidden',
    },
    content: {
        borderTopColor: colors.border,
        borderTopWidth: StyleSheet.hairlineWidth,
        padding: spacing.xl,
    },
    icon: {
        alignItems: 'center',
        backgroundColor: colors.primarySoft,
        borderRadius: radius.full,
        height: 30,
        justifyContent: 'center',
        width: 30,
    },
    label: { flex: 1 },
    trigger: {
        alignItems: 'center',
        backgroundColor: colors.surface,
        flexDirection: 'row',
        minHeight: screen.minTouchTarget,
        paddingHorizontal: spacing.xl,
        paddingVertical: spacing.md,
    },
});
