import { create } from 'zustand';

import { endpoints } from '@/api/endpoints';
import { queryClient } from '@/config/queryClient';
import { removePrivateNavigationState } from '@/services/navigationCache';
import { removePrivateOfflineCache } from '@/services/offlineCache';
import { tokenStorage } from '@/services/tokenStorage';
import type { User } from '@/types/domain';

type Credentials = { email: string; password: string };
type Registration = Credentials & {
    name: string;
    password_confirmation: string;
};

interface AuthState {
    user: User | null;
    isAuthenticated: boolean;
    isRestoring: boolean;
    restoreSession: () => Promise<void>;
    login: (credentials: Credentials) => Promise<void>;
    register: (registration: Registration) => Promise<void>;
    logout: () => Promise<void>;
    deleteAccount: () => Promise<void>;
    expireSession: () => void;
}

async function persistSession(token: string) {
    await tokenStorage.set(token);
}

export const useAuthStore = create<AuthState>((set, get) => ({
    user: null,
    isAuthenticated: false,
    isRestoring: true,
    restoreSession: async () => {
        const token = await tokenStorage.get();
        if (!token) {
            set({ isAuthenticated: false, isRestoring: false, user: null });
            return;
        }
        try {
            const user = await endpoints.user();
            set({ isAuthenticated: true, isRestoring: false, user });
        } catch {
            await tokenStorage.remove();
            set({ isAuthenticated: false, isRestoring: false, user: null });
        }
    },
    login: async credentials => {
        const session = await endpoints.login({
            ...credentials,
            device_name: 'Wariga Android',
        });
        await persistSession(session.token);
        set({ isAuthenticated: true, user: session.user });
    },
    register: async registration => {
        const session = await endpoints.register(registration);
        await persistSession(session.token);
        set({ isAuthenticated: true, user: session.user });
    },
    logout: async () => {
        const userId = get().user?.id;
        try {
            if (get().isAuthenticated) {
                await endpoints.logout();
            }
        } finally {
            if (userId) {
                await Promise.all([
                    removePrivateOfflineCache(queryClient, userId),
                    removePrivateNavigationState(userId),
                ]);
            }
            await tokenStorage.remove();
            set({ isAuthenticated: false, user: null });
        }
    },
    deleteAccount: async () => {
        const userId = get().user?.id;
        await endpoints.deleteAccount();
        if (userId) {
            await Promise.all([
                removePrivateOfflineCache(queryClient, userId),
                removePrivateNavigationState(userId),
            ]);
        }
        await tokenStorage.remove();
        set({ isAuthenticated: false, user: null });
    },
    expireSession: () => set({ isAuthenticated: false, user: null }),
}));
