import notifee from '@notifee/react-native';
import AsyncStorage from '@react-native-async-storage/async-storage';

import {
    defaultReminderSettings,
    isReminderScheduled,
    isValidReminderTime,
    loadReminderSettings,
    reminderTimestamps,
    saveReminderSettings,
    scheduleSavedReminder,
} from '@/services/reminderService';

describe('fase 25 pengingat dan notifikasi lokal', () => {
    beforeEach(async () => {
        await AsyncStorage.clear();
        jest.clearAllMocks();
    });

    it('tidak meminta permission ketika pengaturan hanya dimuat', async () => {
        await expect(loadReminderSettings()).resolves.toEqual(defaultReminderSettings);
        expect(notifee.requestPermission).not.toHaveBeenCalled();
    });

    it('memvalidasi waktu dan membuat jadwal H-1 serta hari-H', () => {
        expect(isValidReminderTime('08:30')).toBe(true);
        expect(isValidReminderTime('25:00')).toBe(false);
        const settings = { ...defaultReminderSettings, enabled: true, time: '08:30' };
        const schedules = reminderTimestamps(
            '2026-08-10',
            settings,
            new Date('2026-08-01T00:00:00').getTime(),
        );
        expect(schedules.map(item => item.kind)).toEqual(['day-before', 'same-day']);
        expect(schedules[1].timestamp - schedules[0].timestamp).toBe(86_400_000);
    });

    it('menjadwalkan hasil tersimpan dengan preferensi pengguna', async () => {
        await saveReminderSettings({
            enabled: true,
            time: '09:00',
            dayBefore: true,
            sameDay: false,
        });
        await scheduleSavedReminder({
            activityName: 'Pernikahan',
            date: '2099-08-10',
            savedId: 42,
        });
        expect(await isReminderScheduled(42)).toBe(true);
        expect(notifee.createTriggerNotification).toHaveBeenCalledTimes(1);
    });
});
