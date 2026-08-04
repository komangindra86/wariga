import { StyleSheet, View } from 'react-native';

import { AppText } from './AppText';

import { colors, spacing, zIndex } from '@/theme';

export function OfflineBanner({ visible }: { visible: boolean }) {
    if (!visible) return null;
    return (
        <View accessibilityLiveRegion="polite" style={styles.banner}>
            <AppText color="textInverse" variant="bodySmall" weight="medium">
                Anda sedang offline. Data yang ditampilkan berasal dari
                penyimpanan terakhir di perangkat.
            </AppText>
        </View>
    );
}

const styles = StyleSheet.create({
    banner: {
        alignItems: 'center',
        backgroundColor: colors.warning,
        paddingHorizontal: spacing.lg,
        paddingVertical: spacing.sm,
        zIndex: zIndex.banner,
    },
});
