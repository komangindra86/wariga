import { Platform } from 'react-native';

import { colors } from './colors';
import { spacing } from './spacing';
import { typography } from './typography';

export { colors, spacing, typography };

export const radius = {
    none: 0,
    sm: 8,
    md: 14,
    lg: 20,
    xl: 28,
    full: 999,
} as const;

export const shadows = {
    none: {},
    sm: Platform.select({
        android: { elevation: 1 },
        ios: {
            shadowColor: colors.text,
            shadowOffset: { width: 0, height: 1 },
            shadowOpacity: 0.06,
            shadowRadius: 5,
        },
        default: {},
    }),
    md: Platform.select({
        android: { elevation: 3 },
        ios: {
            shadowColor: colors.text,
            shadowOffset: { width: 0, height: 3 },
            shadowOpacity: 0.09,
            shadowRadius: 12,
        },
        default: {},
    }),
} as const;

export const iconSize = { sm: 16, md: 20, lg: 24, xl: 32 } as const;

export const screen = {
    horizontalPadding: spacing.xl,
    maxContentWidth: 720,
    minTouchTarget: 48,
    tabletBreakpoint: 768,
} as const;

export const zIndex = { base: 0, sticky: 10, banner: 20, modal: 100 } as const;

export const theme = {
    colors,
    iconSize,
    radius,
    screen,
    shadows,
    spacing,
    typography,
    zIndex,
} as const;

export type AppTheme = typeof theme;
