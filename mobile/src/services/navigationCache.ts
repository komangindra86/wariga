import AsyncStorage from '@react-native-async-storage/async-storage';
import type {
    InitialState,
    NavigationState,
    PartialState,
} from '@react-navigation/native';

// v2: tab Cari ditambahkan; state lama tanpa tab itu tidak dipulihkan.
const PREFIX = '@wariga/navigation/v2';
const TTL = 24 * 60 * 60_000;

type StoredNavigation = {
    expiresAt: number;
    state: InitialState;
};

function key(userId: number | null) {
    return `${PREFIX}/${userId === null ? 'public' : `user-${userId}`}`;
}

export async function loadNavigationState(userId: number | null) {
    const storageKey = key(userId);
    const raw = await AsyncStorage.getItem(storageKey);
    if (!raw) return undefined;
    try {
        const stored = JSON.parse(raw) as StoredNavigation;
        if (stored.expiresAt <= Date.now()) {
            await AsyncStorage.removeItem(storageKey);
            return undefined;
        }
        return stored.state;
    } catch {
        await AsyncStorage.removeItem(storageKey);
        return undefined;
    }
}

export async function saveNavigationState(
    userId: number | null,
    state: NavigationState | PartialState<NavigationState> | undefined,
) {
    if (!state) return;
    await AsyncStorage.setItem(
        key(userId),
        JSON.stringify({ expiresAt: Date.now() + TTL, state }),
    );
}

export async function removePrivateNavigationState(userId: number) {
    await AsyncStorage.removeItem(key(userId));
}
