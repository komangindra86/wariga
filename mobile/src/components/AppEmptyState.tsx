import { StyleSheet, View } from 'react-native';

import { AppButton } from './AppButton';
import { AppText } from './AppText';

import { spacing } from '@/theme';

type Props = {
    title: string;
    message: string;
    actionLabel?: string;
    onAction?: () => void;
};
export function AppEmptyState({
    actionLabel,
    message,
    onAction,
    title,
}: Props) {
    return (
        <View style={styles.container}>
            <AppText variant="titleSmall" weight="semibold">
                {title}
            </AppText>
            <AppText color="textMuted" style={styles.center}>
                {message}
            </AppText>
            {actionLabel && onAction ? (
                <AppButton
                    label={actionLabel}
                    onPress={onAction}
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
