import { useQueryClient } from '@tanstack/react-query';
import { useEffect, useState, type PropsWithChildren } from 'react';

import {
    observeOfflineCache,
    restoreOfflineCache,
} from '@/services/offlineCache';
import { useAuthStore } from '@/store/authStore';

export function OfflineCacheBootstrap({ children }: PropsWithChildren) {
    const client = useQueryClient();
    const isRestoring = useAuthStore(state => state.isRestoring);
    const userId = useAuthStore(state => state.user?.id ?? null);
    const [readyScope, setReadyScope] = useState<string | null>(null);
    const scope = isRestoring ? 'restoring' : `user-${userId ?? 'public'}`;

    useEffect(() => {
        if (isRestoring) return;
        let active = true;
        Promise.all([
            restoreOfflineCache(client, null),
            userId === null
                ? Promise.resolve()
                : restoreOfflineCache(client, userId),
        ]).finally(() => {
            if (active) setReadyScope(scope);
        });
        return () => {
            active = false;
        };
    }, [client, isRestoring, scope, userId]);

    useEffect(
        () =>
            observeOfflineCache(
                client,
                () => useAuthStore.getState().user?.id ?? null,
            ),
        [client],
    );

    if (isRestoring || readyScope !== scope) return null;
    return children;
}
