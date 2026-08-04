import AsyncStorage from '@react-native-async-storage/async-storage';
import { QueryClient } from '@tanstack/react-query';

import {
    persistOfflineCache,
    removePrivateOfflineCache,
    restoreOfflineCache,
} from '@/services/offlineCache';

function client() {
    return new QueryClient({
        defaultOptions: { queries: { retry: false } },
    });
}

beforeEach(async () => {
    await AsyncStorage.clear();
});

describe('fase 24 offline cache', () => {
    it('memulihkan data publik yang pernah berhasil dibuka', async () => {
        const source = client();
        source.setQueryData(['calendar', 'rich-v3', 'date', '2026-07-30'], {
            date: '2026-07-30',
        });
        await persistOfflineCache(source, null);

        const restored = client();
        await restoreOfflineCache(restored, null);

        expect(
            restored.getQueryData([
                'calendar',
                'rich-v3',
                'date',
                '2026-07-30',
            ]),
        ).toEqual({ date: '2026-07-30' });
    });

    it('memisahkan data tersimpan antar akun', async () => {
        const first = client();
        first.setQueryData(['saved-dewasa'], [{ id: 11 }]);
        await persistOfflineCache(first, 1);

        const second = client();
        await restoreOfflineCache(second, 2);
        expect(second.getQueryData(['saved-dewasa'])).toBeUndefined();
    });

    it('menghapus cache pribadi dan query memori ketika logout', async () => {
        const current = client();
        current.setQueryData(['saved-dewasa'], [{ id: 11 }]);
        await persistOfflineCache(current, 1);
        await removePrivateOfflineCache(current, 1);

        expect(current.getQueryData(['saved-dewasa'])).toBeUndefined();
        const fresh = client();
        await restoreOfflineCache(fresh, 1);
        expect(fresh.getQueryData(['saved-dewasa'])).toBeUndefined();
    });

    it('menolak cache dari engine version lama', async () => {
        const old = client();
        old.setQueryData(['dewasa', 'detail', 'melukat', '2026-08-01'], {
            engine_version: 'engine-old',
        });
        await persistOfflineCache(old, null);

        const current = client();
        current.setQueryData(['dewasa', 'detail', 'melukat', '2026-08-02'], {
            engine_version: 'engine-new',
        });
        await persistOfflineCache(current, null);

        const restored = client();
        await restoreOfflineCache(restored, null);
        expect(
            restored.getQueryData([
                'dewasa',
                'detail',
                'melukat',
                '2026-08-01',
            ]),
        ).toBeUndefined();
    });
});
