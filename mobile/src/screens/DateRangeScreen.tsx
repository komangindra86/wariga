import type { NativeStackScreenProps } from '@react-navigation/native-stack';
import { useQuery } from '@tanstack/react-query';
import { format, parseISO } from 'date-fns';
import { id } from 'date-fns/locale';
import { useMemo, useState } from 'react';
import { Pressable, StyleSheet, View } from 'react-native';

import { endpoints } from '@/api/endpoints';
import { queryKeys } from '@/api/queryKeys';
import {
    AppButton,
    AppCard,
    AppDatePicker,
    AppText,
    ScreenContainer,
} from '@/components';
import { useConnectivity } from '@/hooks/useConnectivity';
import { colors, radius, spacing } from '@/theme';
import type { RootStackParamList } from '@/types/navigation';
import {
    makassarToday,
    presetDateRange,
    validateDateRange,
    type DateRangePreset,
} from '@/utils/dateRange';

type Props = NativeStackScreenProps<RootStackParamList, 'DateRange'>;
const presets: Array<{
    key: Exclude<DateRangePreset, 'custom'>;
    label: string;
}> = [
    { key: 'next_7_days', label: '7 hari ke depan' },
    { key: 'next_30_days', label: '30 hari ke depan' },
    { key: 'this_month', label: 'Bulan ini' },
    { key: 'next_month', label: 'Bulan depan' },
];

export function DateRangeScreen({ navigation, route }: Props) {
    const isConnected = useConnectivity();
    const activityId = route.params?.activityId;
    const activities = useQuery({
        queryFn: endpoints.activities,
        queryKey: queryKeys.activities.all,
    });
    const activity = activities.data?.find(item => item.id === activityId);
    const initial = presetDateRange('next_7_days', makassarToday());
    const [preset, setPreset] = useState<DateRangePreset>('next_7_days');
    const [startDate, setStartDate] = useState(initial.startDate);
    const [endDate, setEndDate] = useState(initial.endDate);
    const validationError = useMemo(
        () => validateDateRange({ startDate, endDate }),
        [endDate, startDate],
    );
    const selectPreset = (key: Exclude<DateRangePreset, 'custom'>) => {
        const range = presetDateRange(key);
        setPreset(key);
        setStartDate(range.startDate);
        setEndDate(range.endDate);
    };

    return (
        <ScreenContainer style={styles.content}>
            <View style={styles.intro}>
                <AppText variant="title" weight="bold">
                    Pilih rentang waktu
                </AppText>
                <AppText color="textMuted">
                    Tentukan periode untuk mencari hari yang paling sesuai.
                </AppText>
            </View>
            <AppCard style={styles.activity}>
                <AppText color="textMuted" variant="caption">
                    KEGIATAN TERPILIH
                </AppText>
                <AppText variant="titleSmall" weight="semibold">
                    {activity
                        ? `${activity.icon ? `${activity.icon} ` : ''}${
                              activity.name
                          }`
                        : activities.isPending
                        ? 'Memuat kegiatan…'
                        : 'Kegiatan tidak ditemukan'}
                </AppText>
                <AppButton
                    label="Ganti kegiatan"
                    onPress={() => navigation.replace('ActivityList')}
                    variant="ghost"
                />
            </AppCard>
            <View style={styles.rangeSection}>
                <View style={styles.sectionHeading}>
                    <AppText variant="titleSmall" weight="semibold">Pilih periode</AppText>
                    <AppText color="textMuted" variant="caption">Maksimal 366 hari</AppText>
                </View>
                <View style={styles.presets}>
                {presets.map(item => (
                    <Pressable
                        accessibilityRole="button"
                        key={item.key}
                        onPress={() => selectPreset(item.key)}
                        style={[
                            styles.preset,
                            preset === item.key && styles.presetSelected,
                        ]}
                    >
                        <AppText
                            color={preset === item.key ? 'textInverse' : 'text'}
                            variant="bodySmall"
                            weight="medium"
                        >
                            {item.label}
                        </AppText>
                    </Pressable>
                ))}
                <Pressable
                    accessibilityRole="button"
                    onPress={() => setPreset('custom')}
                    style={[
                        styles.preset,
                        styles.customPreset,
                        preset === 'custom' && styles.presetSelected,
                    ]}
                >
                    <AppText
                        color={preset === 'custom' ? 'textInverse' : 'text'}
                        variant="bodySmall"
                        weight="medium"
                    >
                        Pilih tanggal sendiri
                    </AppText>
                </Pressable>
                </View>
            </View>
            {preset === 'custom' ? (
                <View style={styles.custom}>
                    <AppDatePicker
                        label="Tanggal awal"
                        onChange={date => {
                            setStartDate(date);
                            if (date > endDate) setEndDate(date);
                        }}
                        value={startDate}
                    />
                    <AppDatePicker
                        label="Tanggal akhir"
                        maximumDate={new Date(parseISO(startDate).getTime() + 365 * 86_400_000)}
                        minimumDate={parseISO(startDate)}
                        onChange={setEndDate}
                        value={endDate}
                    />
                    {validationError ? <AppText color="danger" variant="caption">{validationError}</AppText> : null}
                </View>
            ) : null}
            {!validationError ? (
                <AppCard style={styles.summary}>
                    <AppText color="textMuted" variant="caption">
                        RENTANG TERPILIH
                    </AppText>
                    <AppText weight="semibold">
                        {format(parseISO(startDate), 'd MMMM yyyy', {
                            locale: id,
                        })}{' '}
                        –{' '}
                        {format(parseISO(endDate), 'd MMMM yyyy', {
                            locale: id,
                        })}
                    </AppText>
                </AppCard>
            ) : null}
            <View style={styles.action}>
            <AppButton
                disabled={
                    Boolean(validationError) ||
                    !activity ||
                    isConnected === false
                }
                fullWidth
                label="Cari rekomendasi"
                onPress={() =>
                    navigation.navigate('SearchResult', {
                        activityId,
                        endDate,
                        startDate,
                    })
                }
            />
            </View>
            {isConnected === false ? (
                <AppText color="warning" variant="bodySmall">
                    Pencarian baru membutuhkan koneksi internet. Buka kembali
                    hasil yang pernah dilihat melalui halaman terakhir.
                </AppText>
            ) : null}
        </ScreenContainer>
    );
}

const styles = StyleSheet.create({
    activity: { gap: spacing.sm },
    action: { marginTop: spacing.sm },
    content: { gap: spacing.xl },
    custom: { gap: spacing.md },
    customPreset: { width: '100%' },
    intro: { gap: spacing.sm },
    preset: {
        backgroundColor: colors.surface,
        borderColor: colors.border,
        borderRadius: radius.md,
        borderWidth: 1,
        justifyContent: 'center',
        minHeight: 54,
        paddingHorizontal: spacing.md,
        paddingVertical: spacing.sm,
        width: '48%',
    },
    presetSelected: {
        backgroundColor: colors.primary,
        borderColor: colors.primary,
    },
    presets: { flexDirection: 'row', flexWrap: 'wrap', gap: spacing.sm },
    rangeSection: { gap: spacing.md },
    sectionHeading: {
        alignItems: 'center',
        flexDirection: 'row',
        justifyContent: 'space-between',
    },
    summary: { gap: spacing.xs },
});
