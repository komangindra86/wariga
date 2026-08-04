import AsyncStorage from '@react-native-async-storage/async-storage';
import {
    dehydrate,
    hydrate,
    type DehydratedState,
    type QueryClient,
} from '@tanstack/react-query';

const CACHE_SCHEMA = 1;
const PUBLIC_TTL = 7 * 24 * 60 * 60_000;
const PRIVATE_TTL = 24 * 60 * 60_000;
const KEY_PREFIX = '@wariga/offline-cache/v1';
const ENGINE_KEY = `${KEY_PREFIX}/engine`;

type CacheEnvelope = {
    schema: number;
    savedAt: number;
    expiresAt: number;
    engineVersion: string | null;
    state: DehydratedState;
};

const publicRoots = new Set(['activities', 'calendar', 'dewasa']);

function cacheKey(userId: number | null) {
    return `${KEY_PREFIX}/${userId === null ? 'public' : `user-${userId}`}`;
}

function isPrivateQuery(queryKey: readonly unknown[]) {
    return queryKey[0] === 'saved-dewasa' || queryKey[0] === 'user';
}

function isPersistable(queryKey: readonly unknown[], userId: number | null) {
    const root = queryKey[0];
    return userId === null
        ? typeof root === 'string' && publicRoots.has(root)
        : isPrivateQuery(queryKey);
}

function findEngineVersion(value: unknown): string | null {
    if (!value || typeof value !== 'object') return null;
    if (Array.isArray(value)) {
        for (const item of value) {
            const version = findEngineVersion(item);
            if (version) return version;
        }
        return null;
    }
    const record = value as Record<string, unknown>;
    if (typeof record.engine_version === 'string') {
        return record.engine_version;
    }
    for (const nested of Object.values(record)) {
        const version = findEngineVersion(nested);
        if (version) return version;
    }
    return null;
}

async function readEnvelope(userId: number | null) {
    const key = cacheKey(userId);
    const raw = await AsyncStorage.getItem(key);
    if (!raw) return null;
    try {
        const envelope = JSON.parse(raw) as CacheEnvelope;
        const knownEngine = await AsyncStorage.getItem(ENGINE_KEY);
        if (
            envelope.schema !== CACHE_SCHEMA ||
            envelope.expiresAt <= Date.now() ||
            (knownEngine !== null &&
                envelope.engineVersion !== null &&
                envelope.engineVersion !== knownEngine)
        ) {
            await AsyncStorage.removeItem(key);
            return null;
        }
        return envelope;
    } catch {
        await AsyncStorage.removeItem(key);
        return null;
    }
}

export async function restoreOfflineCache(
    client: QueryClient,
    userId: number | null,
) {
    const envelope = await readEnvelope(userId);
    if (envelope) hydrate(client, envelope.state);
}

export async function persistOfflineCache(
    client: QueryClient,
    userId: number | null,
) {
    const state = dehydrate(client, {
        shouldDehydrateQuery: query =>
            query.state.status === 'success' &&
            isPersistable(query.queryKey, userId),
    });
    let engineVersion: string | null = null;
    for (const query of state.queries) {
        engineVersion =
            findEngineVersion(query.state.data) ?? engineVersion;
    }
    if (engineVersion) {
        const knownEngine = await AsyncStorage.getItem(ENGINE_KEY);
        if (knownEngine && knownEngine !== engineVersion) {
            const keys = await AsyncStorage.getAllKeys();
            await Promise.all(
                keys
                    .filter(
                        key =>
                            key.startsWith(`${KEY_PREFIX}/`) &&
                            key !== ENGINE_KEY,
                    )
                    .map(key => AsyncStorage.removeItem(key)),
            );
            client.removeQueries({
                predicate: query =>
                    query.queryKey[0] === 'dewasa' ||
                    query.queryKey[0] === 'saved-dewasa',
            });
        }
        await AsyncStorage.setItem(ENGINE_KEY, engineVersion);
    }
    const savedAt = Date.now();
    const envelope: CacheEnvelope = {
        engineVersion,
        expiresAt:
            savedAt + (userId === null ? PUBLIC_TTL : PRIVATE_TTL),
        savedAt,
        schema: CACHE_SCHEMA,
        state,
    };
    await AsyncStorage.setItem(cacheKey(userId), JSON.stringify(envelope));
}

export async function removePrivateOfflineCache(
    client: QueryClient,
    userId: number,
) {
    await AsyncStorage.removeItem(cacheKey(userId));
    client.removeQueries({
        predicate: query => isPrivateQuery(query.queryKey),
    });
}

export function observeOfflineCache(
    client: QueryClient,
    getUserId: () => number | null,
) {
    let timer: ReturnType<typeof setTimeout> | undefined;
    const unsubscribe = client.getQueryCache().subscribe(event => {
        if (event.type !== 'updated' || event.query.state.status !== 'success') {
            return;
        }
        clearTimeout(timer);
        timer = setTimeout(() => {
            const userId = isPrivateQuery(event.query.queryKey)
                ? getUserId()
                : null;
            if (isPersistable(event.query.queryKey, userId)) {
                persistOfflineCache(client, userId).catch(() => undefined);
            }
        }, 250);
    });
    return () => {
        clearTimeout(timer);
        unsubscribe();
    };
}
