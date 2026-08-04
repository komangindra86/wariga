import { QueryClientProvider } from '@tanstack/react-query';
import type { PropsWithChildren } from 'react';
import { useEffect } from 'react';
import { StatusBar } from 'react-native';
import { SafeAreaProvider } from 'react-native-safe-area-context';

import { endpoints } from '@/api/endpoints';
import { AuthSessionBootstrap } from '@/components/AuthSessionBootstrap';
import { OfflineBanner } from '@/components/OfflineBanner';
import { OfflineCacheBootstrap } from '@/components/OfflineCacheBootstrap';
import { queryClient } from '@/config/queryClient';
import { useConnectivity } from '@/hooks/useConnectivity';
import { loadReminderSettings, scheduleCalendarEventReminders } from '@/services/reminderService';

declare function requestIdleCallback(
    callback: () => void,
    options?: { timeout: number },
): number;
declare function cancelIdleCallback(handle: number): void;

function ConnectedApp({ children }: PropsWithChildren) {
    const isConnected = useConnectivity();
    useEffect(() => {
        if (!isConnected) return;
        let timer: ReturnType<typeof setTimeout> | undefined;
        const idleCallback = requestIdleCallback(() => {
            timer = setTimeout(() => {
                loadReminderSettings().then(async settings => {
                    if (!settings.enabled || !settings.calendarEvents) return;
                    const events = await endpoints.upcomingCalendarEvents();
                    await scheduleCalendarEventReminders(events, settings);
                }).catch(() => undefined);
            }, 1500);
        }, { timeout: 3000 });
        return () => {
            cancelIdleCallback(idleCallback);
            if (timer) clearTimeout(timer);
        };
    }, [isConnected]);
    return (
        <AuthSessionBootstrap>
            <OfflineCacheBootstrap>
                <OfflineBanner visible={isConnected === false} />
                {children}
            </OfflineCacheBootstrap>
        </AuthSessionBootstrap>
    );
}

export function AppProviders({ children }: PropsWithChildren) {
    return (
        <SafeAreaProvider>
            <QueryClientProvider client={queryClient}>
                <StatusBar barStyle="dark-content" />
                <ConnectedApp>{children}</ConnectedApp>
            </QueryClientProvider>
        </SafeAreaProvider>
    );
}
