import type { PropsWithChildren } from 'react';
import { StyleSheet, Text, type TextProps, type TextStyle } from 'react-native';

import { colors, typography } from '@/theme';

type Variant =
    | 'caption'
    | 'bodySmall'
    | 'body'
    | 'titleSmall'
    | 'title'
    | 'display';

type Props = TextProps & {
    variant?: Variant;
    color?: keyof typeof colors;
    weight?: keyof typeof typography.fontWeight;
};

export function AppText({
    children,
    color = 'text',
    style,
    variant = 'body',
    weight = 'regular',
    ...props
}: PropsWithChildren<Props>) {
    return (
        <Text
            {...props}
            style={[
                styles.base,
                {
                    color: colors[color],
                    fontSize: typography.fontSize[variant],
                    fontWeight: typography.fontWeight[
                        weight
                    ] as TextStyle['fontWeight'],
                    lineHeight: typography.lineHeight[variant],
                },
                style,
            ]}
        >
            {children}
        </Text>
    );
}

const styles = StyleSheet.create({
    base: { fontFamily: typography.fontFamily.regular },
});
