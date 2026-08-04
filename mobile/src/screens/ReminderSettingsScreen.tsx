import type { NativeStackScreenProps } from '@react-navigation/native-stack';
import { useEffect, useState } from 'react';
import { Alert, StyleSheet, Switch, View } from 'react-native';

import { endpoints } from '@/api/endpoints';
import { AppButton, AppCard, AppInput, AppLoader, AppText, ScreenContainer } from '@/components';
import {
    defaultReminderSettings,
    isValidReminderTime,
    loadReminderSettings,
    requestReminderPermission,
    scheduleCalendarEventReminders,
    saveReminderSettings,
    type ReminderSettings,
} from '@/services/reminderService';
import { colors, spacing } from '@/theme';
import type { RootStackParamList } from '@/types/navigation';

type Props = NativeStackScreenProps<RootStackParamList, 'ReminderSettings'>;

export function ReminderSettingsScreen({}: Props) {
    const [settings, setSettings] = useState<ReminderSettings>();
    const [saving, setSaving] = useState(false);

    useEffect(() => {
        loadReminderSettings().then(setSettings);
    }, []);

    if (!settings) return <AppLoader label="Memuat pengaturan..." />;

    const updateEnabled = async (enabled: boolean) => {
        if (enabled && !(await requestReminderPermission())) {
            Alert.alert(
                'Izin notifikasi diperlukan',
                'Aktifkan izin notifikasi Wariga melalui pengaturan perangkat.',
            );
            return;
        }
        setSettings(current => ({ ...(current ?? defaultReminderSettings), enabled }));
    };
    const save = async () => {
        if (!isValidReminderTime(settings.time)) {
            Alert.alert('Waktu tidak valid', 'Gunakan format 24 jam HH:MM.');
            return;
        }
        if (settings.enabled && !settings.dayBefore && !settings.sameDay && !settings.calendarEvents) {
            Alert.alert('Pilih waktu pengingat', 'Aktifkan minimal satu pilihan pengingat.');
            return;
        }
        setSaving(true);
        try {
            await saveReminderSettings(settings);
            const events = settings.calendarEvents ? await endpoints.upcomingCalendarEvents() : [];
            await scheduleCalendarEventReminders(events, settings);
            Alert.alert('Tersimpan', 'Pengaturan pengingat telah diperbarui.');
        } finally {
            setSaving(false);
        }
    };

    return (
        <ScreenContainer style={styles.content}>
            <AppText variant="title" weight="bold">Pengingat</AppText>
            <AppText color="textMuted">
                Atur pengingat hasil tersimpan dan rahina Kalender Bali. Semua notifikasi diproses secara lokal di perangkat.
            </AppText>
            <AppCard style={styles.card}>
                <SettingRow
                    label="Aktifkan pengingat"
                    onValueChange={updateEnabled}
                    value={settings.enabled}
                />
                <AppInput
                    editable={settings.enabled}
                    helperText="Format 24 jam, contoh 08:00"
                    label="Waktu notifikasi"
                    maxLength={5}
                    onChangeText={time => setSettings({ ...settings, time })}
                    placeholder="08:00"
                    value={settings.time}
                />
                <SettingRow
                    disabled={!settings.enabled}
                    label="Rahina dan kalender otomatis"
                    onValueChange={calendarEvents => setSettings({ ...settings, calendarEvents })}
                    value={Boolean(settings.calendarEvents)}
                />
                <AppText color="textMuted" variant="caption">
                    Kajeng Kliwon 1 hari sebelumnya, Purnama 3 hari, Tilem 7 hari, dan Galungan 20 hari sebelumnya.
                </AppText>
                <AppText variant="bodySmall" weight="semibold">Hasil Dewasa Ayu tersimpan</AppText>
                <SettingRow
                    disabled={!settings.enabled}
                    label="Satu hari sebelumnya"
                    onValueChange={dayBefore => setSettings({ ...settings, dayBefore })}
                    value={settings.dayBefore}
                />
                <SettingRow
                    disabled={!settings.enabled}
                    label="Pada hari yang sama"
                    onValueChange={sameDay => setSettings({ ...settings, sameDay })}
                    value={settings.sameDay}
                />
            </AppCard>
            <AppButton fullWidth label="Simpan pengaturan" loading={saving} onPress={save} />
        </ScreenContainer>
    );
}

function SettingRow({
    disabled,
    label,
    onValueChange,
    value,
}: {
    disabled?: boolean;
    label: string;
    onValueChange: (value: boolean) => void;
    value: boolean;
}) {
    return (
        <View style={styles.row}>
            <AppText>{label}</AppText>
            <Switch
                disabled={disabled}
                onValueChange={onValueChange}
                thumbColor={value ? colors.primary : colors.textMuted}
                value={value}
            />
        </View>
    );
}

const styles = StyleSheet.create({
    card: { gap: spacing.lg },
    content: { gap: spacing.lg },
    row: {
        alignItems: 'center',
        flexDirection: 'row',
        justifyContent: 'space-between',
    },
});
