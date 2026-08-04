import { forwardRef } from 'react';
import { StyleSheet, TextInput, View, type TextInputProps } from 'react-native';

import { AppText } from '@/components/AppText';
import { colors, radius, spacing, typography } from '@/theme';

type Props = TextInputProps & {
    label?: string;
    error?: string;
    helperText?: string;
};

export const AppInput = forwardRef<TextInput, Props>(function InputField(
    { error, helperText, label, style, ...props },
    ref,
) {
    return (
        <View style={styles.wrapper}>
            {label ? (
                <AppText variant="bodySmall" weight="medium">
                    {label}
                </AppText>
            ) : null}
            <TextInput
                {...props}
                ref={ref}
                placeholderTextColor={colors.textMuted}
                style={[styles.input, error ? styles.inputError : null, style]}
            />
            {error || helperText ? (
                <AppText
                    color={error ? 'danger' : 'textMuted'}
                    variant="caption"
                >
                    {error ?? helperText}
                </AppText>
            ) : null}
        </View>
    );
});

const styles = StyleSheet.create({
    input: {
        backgroundColor: colors.surface,
        borderColor: colors.border,
        borderRadius: radius.lg,
        borderWidth: 1,
        color: colors.text,
        fontSize: typography.fontSize.body,
        minHeight: 52,
        paddingHorizontal: spacing.lg,
        paddingVertical: spacing.md,
    },
    inputError: { borderColor: colors.danger },
    wrapper: { gap: spacing.sm },
});
