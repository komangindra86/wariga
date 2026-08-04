import { useEffect, type PropsWithChildren } from 'react';

import { setSessionExpiredHandler } from '@/services/authSessionEvents';
import { useAuthStore } from '@/store/authStore';

export function AuthSessionBootstrap({ children }: PropsWithChildren) {
    const restoreSession = useAuthStore(state => state.restoreSession);
    const expireSession = useAuthStore(state => state.expireSession);

    useEffect(() => {
        setSessionExpiredHandler(expireSession);
        restoreSession().catch(() => undefined);
        return () => setSessionExpiredHandler(null);
    }, [expireSession, restoreSession]);

    return children;
}
