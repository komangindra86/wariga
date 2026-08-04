import type { NativeStackScreenProps } from '@react-navigation/native-stack';
import { format } from 'date-fns';
import { useState } from 'react';
import { Alert, Pressable, StyleSheet, Switch, View } from 'react-native';

import { endpoints } from '@/api/endpoints';
import { AppButton, AppCard, AppDatePicker, AppInput, AppText, ScreenContainer } from '@/components';
import { colors, radius, spacing } from '@/theme';
import type { RootStackParamList } from '@/types/navigation';

type Props = NativeStackScreenProps<RootStackParamList, 'Contribution'>;
type ContributionType = 'app_feedback' | 'result_report' | 'wariga_knowledge';
const choices: Array<{ key: ContributionType; label: string; description: string }> = [
    { key: 'app_feedback', label: 'Kritik & saran aplikasi', description: 'Tampilan, fitur, performa, atau kesalahan teks.' },
    { key: 'result_report', label: 'Laporkan hasil', description: 'Hasil Dewasa Ayu yang berbeda dengan praktik atau rujukan.' },
    { key: 'wariga_knowledge', label: 'Pengetahuan Wariga', description: 'Aturan, koreksi, sumber, atau keterangan narasumber.' },
];

export function ContributionScreen({ navigation }: Props) {
    const [type, setType] = useState<ContributionType>('app_feedback');
    const [anonymous, setAnonymous] = useState(true);
    const [consent, setConsent] = useState(false);
    const [saving, setSaving] = useState(false);
    const [email, setEmail] = useState('');
    const [subject, setSubject] = useState('');
    const [message, setMessage] = useState('');
    const [activity, setActivity] = useState('');
    const [date, setDate] = useState(format(new Date(), 'yyyy-MM-dd'));
    const [region, setRegion] = useState('');
    const [tradition, setTradition] = useState('');
    const [source, setSource] = useState('');

    const submit = async () => {
        if (subject.trim().length < 5 || message.trim().length < 15 || !consent) {
            Alert.alert('Form belum lengkap', 'Isi judul, penjelasan minimal 15 karakter, dan persetujuan penggunaan data.');
            return;
        }
        if (!anonymous && !email.trim()) {
            Alert.alert('Email diperlukan', 'Masukkan email agar kami dapat meminta klarifikasi.');
            return;
        }
        if (type === 'result_report' && !activity.trim()) {
            Alert.alert('Kegiatan diperlukan', 'Tuliskan kegiatan pada hasil yang Anda laporkan.');
            return;
        }
        if (type === 'wariga_knowledge' && !source.trim()) {
            Alert.alert('Sumber diperlukan', 'Tuliskan sumber buku, lontar, tautan, atau narasumber.');
            return;
        }
        setSaving(true);
        try {
            const result = await endpoints.submitContribution({
                type, is_anonymous: anonymous, contact_email: anonymous ? undefined : email.trim(),
                subject: subject.trim(), message: message.trim(), activity_name: type === 'result_report' ? activity.trim() : undefined,
                related_date: type === 'result_report' ? date : undefined, region: region.trim() || undefined,
                tradition: tradition.trim() || undefined, source_reference: type === 'wariga_knowledge' ? source.trim() : undefined, consent: true,
            });
            Alert.alert('Kontribusi terkirim', `${result.status_label}. Kiriman tidak langsung mengubah aturan.`, [{ text: 'Selesai', onPress: () => navigation.goBack() }]);
        } catch (error) {
            Alert.alert('Belum dapat dikirim', error instanceof Error ? error.message : 'Silakan coba kembali.');
        } finally { setSaving(false); }
    };

    return (
        <ScreenContainer style={styles.content}>
            <View style={styles.intro}><AppText variant="title" weight="bold">Kontribusi & Saran</AppText><AppText color="textMuted">Bantu meningkatkan aplikasi dan basis pengetahuan secara bertanggung jawab.</AppText></View>
            <View style={styles.choices}>{choices.map(choice => <Pressable key={choice.key} onPress={() => setType(choice.key)} style={[styles.choice, type === choice.key && styles.choiceSelected]}><AppText color={type === choice.key ? 'textInverse' : 'text'} weight="semibold">{choice.label}</AppText><AppText color={type === choice.key ? 'secondarySoft' : 'textMuted'} variant="caption">{choice.description}</AppText></Pressable>)}</View>
            <AppCard style={styles.form}>
                <AppInput label="Judul" maxLength={180} onChangeText={setSubject} value={subject} />
                <AppInput label="Penjelasan" multiline numberOfLines={5} onChangeText={setMessage} style={styles.multiline} value={message} />
                {type === 'result_report' ? <><AppInput label="Kegiatan" onChangeText={setActivity} value={activity} /><AppDatePicker label="Tanggal hasil" onChange={setDate} value={date} /></> : null}
                {type !== 'app_feedback' ? <><AppInput label="Wilayah (opsional)" onChangeText={setRegion} value={region} /><AppInput label="Pakem/tradisi (opsional)" onChangeText={setTradition} value={tradition} /></> : null}
                {type === 'wariga_knowledge' ? <AppInput helperText="Buku, lontar, tautan, atau nama dan kapasitas narasumber." label="Sumber rujukan" onChangeText={setSource} value={source} /> : null}
                <Setting label="Kirim secara anonim" onChange={setAnonymous} value={anonymous} />
                {!anonymous ? <AppInput autoCapitalize="none" keyboardType="email-address" label="Email untuk klarifikasi" onChangeText={setEmail} value={email} /> : null}
                <Setting label="Saya setuju data ini digunakan untuk proses tinjauan" onChange={setConsent} value={consent} />
            </AppCard>
            <AppText color="textMuted" variant="caption">Kontribusi masuk sebagai usulan. Perubahan aturan hanya dilakukan setelah ditinjau validator.</AppText>
            <AppButton fullWidth label="Kirim kontribusi" loading={saving} onPress={submit} />
        </ScreenContainer>
    );
}

function Setting({ label, onChange, value }: { label: string; onChange: (value: boolean) => void; value: boolean }) {
    return <View style={styles.setting}><AppText style={styles.settingLabel} variant="bodySmall">{label}</AppText><Switch onValueChange={onChange} thumbColor={value ? colors.primary : colors.textMuted} value={value} /></View>;
}

const styles = StyleSheet.create({
    choice: { backgroundColor: colors.surface, borderColor: colors.border, borderRadius: radius.lg, borderWidth: 1, gap: spacing.xs, padding: spacing.md },
    choiceSelected: { backgroundColor: colors.primary, borderColor: colors.primary }, choices: { gap: spacing.sm }, content: { gap: spacing.xl }, form: { gap: spacing.lg }, intro: { gap: spacing.sm },
    multiline: { minHeight: 120, textAlignVertical: 'top' }, setting: { alignItems: 'center', flexDirection: 'row', gap: spacing.md, justifyContent: 'space-between' }, settingLabel: { flex: 1 },
});
