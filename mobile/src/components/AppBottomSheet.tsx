import type { PropsWithChildren } from 'react';
import { Modal, Pressable, StyleSheet, View } from 'react-native';

import { AppText } from './AppText';

import { colors, radius, screen, shadows, spacing, zIndex } from '@/theme';

type Props = { visible: boolean; title: string; onClose: () => void };
export function AppBottomSheet({
    children,
    onClose,
    title,
    visible,
}: PropsWithChildren<Props>) {
    return (
        <Modal
            animationType="slide"
            onRequestClose={onClose}
            transparent
            visible={visible}
        >
            <View style={styles.overlay}>
                <Pressable
                    accessibilityLabel="Tutup"
                    onPress={onClose}
                    style={StyleSheet.absoluteFill}
                />
                <View style={styles.sheet}>
                    <View style={styles.handle} />
                    <View style={styles.header}>
                        <AppText variant="titleSmall" weight="semibold">
                            {title}
                        </AppText>
                        <Pressable
                            accessibilityLabel="Tutup"
                            accessibilityRole="button"
                            onPress={onClose}
                            style={styles.close}
                        >
                            <AppText color="primary">Tutup</AppText>
                        </Pressable>
                    </View>
                    {children}
                </View>
            </View>
        </Modal>
    );
}

const styles = StyleSheet.create({
    close: { justifyContent: 'center', minHeight: screen.minTouchTarget },
    handle: {
        alignSelf: 'center',
        backgroundColor: colors.border,
        borderRadius: radius.full,
        height: 4,
        width: 44,
    },
    header: {
        alignItems: 'center',
        flexDirection: 'row',
        justifyContent: 'space-between',
    },
    overlay: {
        backgroundColor: colors.overlay,
        flex: 1,
        justifyContent: 'flex-end',
        zIndex: zIndex.modal,
    },
    sheet: {
        backgroundColor: colors.surface,
        borderTopLeftRadius: radius.xl,
        borderTopRightRadius: radius.xl,
        gap: spacing.xl,
        maxHeight: '90%',
        padding: spacing.xl,
        ...shadows.md,
    },
});
