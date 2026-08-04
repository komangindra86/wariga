import { useEffect, useRef } from 'react';
import { Animated, StyleSheet, type ViewStyle } from 'react-native';

import { colors, radius } from '@/theme';

export function AppSkeleton({
    height = 16,
    width = '100%',
}: {
    height?: number;
    width?: ViewStyle['width'];
}) {
    const opacity = useRef(new Animated.Value(0.45)).current;
    useEffect(() => {
        const animation = Animated.loop(
            Animated.sequence([
                Animated.timing(opacity, {
                    duration: 650,
                    toValue: 1,
                    useNativeDriver: true,
                }),
                Animated.timing(opacity, {
                    duration: 650,
                    toValue: 0.45,
                    useNativeDriver: true,
                }),
            ]),
        );
        animation.start();
        return () => animation.stop();
    }, [opacity]);
    return (
        <Animated.View style={[styles.skeleton, { height, opacity, width }]} />
    );
}

const styles = StyleSheet.create({
    skeleton: { backgroundColor: colors.border, borderRadius: radius.sm },
});
