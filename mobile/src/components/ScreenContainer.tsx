import type { PropsWithChildren } from 'react';
import {
    ScrollView,
    StyleSheet,
    View,
    type RefreshControlProps,
    type ViewProps,
} from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';

import { colors, screen, spacing } from '@/theme';

type Props = ViewProps & {
    scroll?: boolean;
    padded?: boolean;
    refreshControl?: React.ReactElement<RefreshControlProps>;
};
export function ScreenContainer({
    children,
    padded = true,
    refreshControl,
    scroll = true,
    style,
    ...props
}: PropsWithChildren<Props>) {
    const insets = useSafeAreaInsets();
    const content = (
        <View
            {...props}
            style={[
                styles.content,
                padded && styles.padded,
                {
                    paddingBottom: Math.max(
                        insets.bottom + spacing.md,
                        spacing.xl,
                    ),
                },
                style,
            ]}
        >
            {children}
        </View>
    );
    return scroll ? (
        <ScrollView
            contentContainerStyle={styles.scrollContent}
            keyboardShouldPersistTaps="handled"
            refreshControl={refreshControl}
            style={styles.screen}
        >
            {content}
        </ScrollView>
    ) : (
        <View style={styles.screen}>{content}</View>
    );
}

const styles = StyleSheet.create({
    content: {
        alignSelf: 'center',
        flexGrow: 1,
        maxWidth: screen.maxContentWidth,
        width: '100%',
    },
    padded: {
        paddingHorizontal: screen.horizontalPadding,
        paddingTop: spacing.xl,
    },
    screen: { backgroundColor: colors.background, flex: 1 },
    scrollContent: { flexGrow: 1 },
});
