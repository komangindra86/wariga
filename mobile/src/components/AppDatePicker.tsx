import DateTimePicker, { type DateTimePickerEvent } from '@react-native-community/datetimepicker';
import { format, parseISO } from 'date-fns';
import { id } from 'date-fns/locale';
import { useState } from 'react';
import { Platform, Pressable, StyleSheet, View } from 'react-native';

import { AppText } from '@/components/AppText';
import { colors, radius, screen, spacing } from '@/theme';

type Props = {
    label: string;
    value: string;
    onChange: (value: string) => void;
    minimumDate?: Date;
    maximumDate?: Date;
};

export function AppDatePicker({ label, maximumDate, minimumDate, onChange, value }: Props) {
    const [visible, setVisible] = useState(false);
    const selectedDate = parseISO(value);
    const handleChange = (event: DateTimePickerEvent, date?: Date) => {
        if (Platform.OS === 'android') setVisible(false);
        if (event.type !== 'dismissed' && date) onChange(format(date, 'yyyy-MM-dd'));
    };

    return (
        <View style={styles.group}>
            <AppText variant="bodySmall" weight="medium">{label}</AppText>
            <Pressable accessibilityRole="button" onPress={() => setVisible(current => !current)} style={({ pressed }) => [styles.field, pressed && styles.pressed]}>
                <AppText weight="medium">{format(selectedDate, 'd MMMM yyyy', { locale: id })}</AppText>
                <AppText color="primary" variant="titleSmall">▣</AppText>
            </Pressable>
            {visible ? (
                <View style={styles.picker}>
                    <DateTimePicker
                        display={Platform.OS === 'ios' ? 'inline' : 'default'}
                        maximumDate={maximumDate}
                        minimumDate={minimumDate}
                        mode="date"
                        onChange={handleChange}
                        value={selectedDate}
                    />
                    {Platform.OS === 'ios' ? (
                        <Pressable onPress={() => setVisible(false)} style={styles.done}>
                            <AppText color="primary" weight="semibold">Selesai</AppText>
                        </Pressable>
                    ) : null}
                </View>
            ) : null}
        </View>
    );
}

const styles = StyleSheet.create({
    done: { alignSelf: 'flex-end', padding: spacing.sm },
    field: {
        alignItems: 'center',
        backgroundColor: colors.surface,
        borderColor: colors.border,
        borderRadius: radius.md,
        borderWidth: 1,
        flexDirection: 'row',
        justifyContent: 'space-between',
        minHeight: screen.minTouchTarget,
        paddingHorizontal: spacing.md,
    },
    group: { gap: spacing.sm },
    picker: { backgroundColor: colors.surface, borderRadius: radius.md, overflow: 'hidden' },
    pressed: { opacity: 0.75 },
});
