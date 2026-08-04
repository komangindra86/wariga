import notifee, {
    AndroidImportance,
    AuthorizationStatus,
    TriggerType,
    type TimestampTrigger,
} from '@notifee/react-native';
import AsyncStorage from '@react-native-async-storage/async-storage';

import type { UpcomingCalendarEvent } from '@/types/domain';

const SETTINGS_KEY = '@wariga/reminders/settings/v1';
const RECORDS_KEY = '@wariga/reminders/records/v1';
const CHANNEL_ID = 'saved-dewasa-reminders';
const CALENDAR_CHANNEL_ID = 'balinese-calendar-reminders';
const CALENDAR_PREFIX = 'wariga-calendar-';

export type ReminderSettings = {
    enabled: boolean;
    time: string;
    dayBefore: boolean;
    sameDay: boolean;
    calendarEvents?: boolean;
};

export type SavedReminder = {
    savedId: number;
    date: string;
    activityName: string;
};

export const defaultReminderSettings: ReminderSettings = {
    enabled: false,
    time: '08:00',
    dayBefore: true,
    sameDay: true,
    calendarEvents: false,
};

function eventLeadDays(name: string) {
    if (name === 'Kajeng Kliwon') return 1;
    if (name === 'Purnama') return 3;
    if (name === 'Tilem') return 7;
    if (name.includes('Galungan')) return 20;
    return 1;
}

function relativeEventMessage(event: UpcomingCalendarEvent, leadDays: number) {
    if (leadDays === 1) return `Besok ${event.name}.`;
    if (leadDays === 7) return `Minggu depan ${event.name}.`;
    return `${leadDays} hari lagi ${event.name}.`;
}

export async function scheduleCalendarEventReminders(events: UpcomingCalendarEvent[], settings: ReminderSettings) {
    const existing = await notifee.getTriggerNotificationIds();
    await Promise.all(existing.filter(id => id.startsWith(CALENDAR_PREFIX)).map(id => notifee.cancelTriggerNotification(id)));
    if (!settings.enabled || !settings.calendarEvents || !isValidReminderTime(settings.time)) return;

    await notifee.createChannel({ id: CALENDAR_CHANNEL_ID, importance: AndroidImportance.HIGH, name: 'Kalender Bali dan rahina' });
    const [hours, minutes] = settings.time.split(':').map(Number);
    await Promise.all(events.map(async event => {
        const leadDays = eventLeadDays(event.name);
        const notificationDate = new Date(`${event.date}T00:00:00`);
        notificationDate.setDate(notificationDate.getDate() - leadDays);
        notificationDate.setHours(hours, minutes, 0, 0);
        if (notificationDate.getTime() <= Date.now()) return;
        const trigger: TimestampTrigger = { timestamp: notificationDate.getTime(), type: TriggerType.TIMESTAMP };
        await notifee.createTriggerNotification({
            android: { channelId: CALENDAR_CHANNEL_ID, pressAction: { id: 'default' } },
            body: relativeEventMessage(event, leadDays),
            data: { date: event.date, eventName: event.name },
            id: `${CALENDAR_PREFIX}${event.date}-${event.name.toLowerCase().replace(/[^a-z0-9]+/g, '-')}`,
            title: 'Pengingat Kalender Bali',
        }, trigger);
    }));
}

export function isValidReminderTime(value: string) {
    return /^([01]\d|2[0-3]):[0-5]\d$/.test(value);
}

export function reminderTimestamps(
    date: string,
    settings: ReminderSettings,
    now = Date.now(),
) {
    if (!settings.enabled || !isValidReminderTime(settings.time)) return [];
    const [hours, minutes] = settings.time.split(':').map(Number);
    const eventDate = new Date(`${date}T00:00:00`);
    eventDate.setHours(hours, minutes, 0, 0);
    const candidates = [
        settings.dayBefore
            ? { kind: 'day-before' as const, timestamp: eventDate.getTime() - 86_400_000 }
            : null,
        settings.sameDay
            ? { kind: 'same-day' as const, timestamp: eventDate.getTime() }
            : null,
    ];
    return candidates.filter(
        (item): item is NonNullable<typeof item> =>
            item !== null && item.timestamp > now,
    );
}

export async function loadReminderSettings(): Promise<ReminderSettings> {
    const raw = await AsyncStorage.getItem(SETTINGS_KEY);
    if (!raw) return defaultReminderSettings;
    try {
        return { ...defaultReminderSettings, ...JSON.parse(raw) };
    } catch {
        return defaultReminderSettings;
    }
}

async function loadRecords(): Promise<SavedReminder[]> {
    const raw = await AsyncStorage.getItem(RECORDS_KEY);
    if (!raw) return [];
    try {
        return JSON.parse(raw);
    } catch {
        return [];
    }
}

const notificationId = (savedId: number, kind: 'day-before' | 'same-day') =>
    `wariga-saved-${savedId}-${kind}`;

async function cancelRecord(record: SavedReminder) {
    await Promise.all([
        notifee.cancelTriggerNotification(notificationId(record.savedId, 'day-before')),
        notifee.cancelTriggerNotification(notificationId(record.savedId, 'same-day')),
    ]);
}

async function scheduleRecord(
    record: SavedReminder,
    settings: ReminderSettings,
) {
    await cancelRecord(record);
    if (!settings.enabled) return;
    await notifee.createChannel({
        id: CHANNEL_ID,
        importance: AndroidImportance.HIGH,
        name: 'Pengingat hasil tersimpan',
    });
    await Promise.all(
        reminderTimestamps(record.date, settings).map(item => {
            const trigger: TimestampTrigger = {
                timestamp: item.timestamp,
                type: TriggerType.TIMESTAMP,
            };
            return notifee.createTriggerNotification(
                {
                    android: {
                        channelId: CHANNEL_ID,
                        pressAction: { id: 'default' },
                    },
                    body:
                        item.kind === 'day-before'
                            ? `Besok adalah waktu untuk ${record.activityName}.`
                            : `Hari ini adalah waktu untuk ${record.activityName}.`,
                    data: { savedId: String(record.savedId) },
                    id: notificationId(record.savedId, item.kind),
                    title: 'Pengingat Dewasa Ayu',
                },
                trigger,
            );
        }),
    );
}

export async function saveReminderSettings(settings: ReminderSettings) {
    await AsyncStorage.setItem(SETTINGS_KEY, JSON.stringify(settings));
    const records = await loadRecords();
    await Promise.all(records.map(record => scheduleRecord(record, settings)));
}

export async function requestReminderPermission() {
    const permission = await notifee.requestPermission();
    return (
        permission.authorizationStatus === AuthorizationStatus.AUTHORIZED ||
        permission.authorizationStatus === AuthorizationStatus.PROVISIONAL
    );
}

export async function isReminderScheduled(savedId: number) {
    return (await loadRecords()).some(record => record.savedId === savedId);
}

export async function scheduleSavedReminder(record: SavedReminder) {
    const settings = await loadReminderSettings();
    if (!settings.enabled) throw new Error('REMINDERS_DISABLED');
    if (reminderTimestamps(record.date, settings).length === 0) {
        throw new Error('NO_FUTURE_REMINDER');
    }
    const records = (await loadRecords()).filter(
        item => item.savedId !== record.savedId,
    );
    records.push(record);
    await scheduleRecord(record, settings);
    await AsyncStorage.setItem(RECORDS_KEY, JSON.stringify(records));
}

export async function cancelSavedReminder(savedId: number) {
    const records = await loadRecords();
    const record = records.find(item => item.savedId === savedId);
    if (record) await cancelRecord(record);
    await AsyncStorage.setItem(
        RECORDS_KEY,
        JSON.stringify(records.filter(item => item.savedId !== savedId)),
    );
}
