import type { NativeStackScreenProps } from '@react-navigation/native-stack';

import { AppCard, AppText, ScreenContainer } from '@/components';
import { spacing } from '@/theme';
import type { RootStackParamList } from '@/types/navigation';

type RouteName = Exclude<keyof RootStackParamList, 'MainTabs'>;
type Props = NativeStackScreenProps<RootStackParamList, RouteName> & {
    title: string;
    description: string;
};

const content: Record<string, Array<{ heading: string; body: string }>> = {
    'Kebijakan Privasi': [
        {
            heading: 'Data yang diproses',
            body: 'Aplikasi memproses data akun, hasil Dewasa Ayu yang disimpan, pengaturan pengingat, dan umpan balik yang Anda kirim secara sukarela. Umpan balik dapat memuat kegiatan, tanggal yang sedang dilihat, penilaian, alasan, wilayah, pakem, dan keterangan konsultasi ahli.',
        },
        {
            heading: 'Cara data digunakan',
            body: 'Data akun digunakan untuk autentikasi dan fitur pribadi. Umpan balik hanya digunakan setelah persetujuan untuk analisis agregat dan penyusunan usulan peningkatan sistem. Identitas akun tidak ditampilkan kepada validator.',
        },
        {
            heading: 'Pilihan dan kendali Anda',
            body: 'Alasan, wilayah, pakem, dan keterangan konsultasi bersifat opsional. Anda dapat memperbarui masukan dengan mengirim ulang dari hasil yang sama. Menghapus akun juga menghapus hasil tersimpan dan umpan balik yang terhubung.',
        },
        {
            heading: 'Keamanan dan pembatasan',
            body: 'Token masuk disimpan melalui penyimpanan aman perangkat. Masukan pengguna tidak pernah langsung mengubah skor atau aturan; perubahan tetap memerlukan tinjauan validator.',
        },
    ],
    Disclaimer: [
        {
            heading: 'Panduan budaya',
            body: 'Hasil Wariga Bali Santih adalah bantuan untuk memahami Kalender Bali dan pertimbangan Dewasa Ayu. Hasil bukan kepastian mutlak, fatwa keagamaan, atau pengganti keputusan keluarga dan desa adat.',
        },
        {
            heading: 'Konsultasi untuk keputusan penting',
            body: 'Untuk upacara dan keputusan adat penting, konfirmasikan hasil kepada sulinggih, pemangku, ahli Wariga, atau pihak yang dipercaya sesuai pakem setempat.',
        },
        {
            heading: 'Perhitungan dan verifikasi',
            body: 'Tanggal dapat berasal dari perhitungan otomatis atau data yang sudah dibandingkan dengan referensi eksternal. Status pada hasil menjelaskan tingkat verifikasinya. Perbedaan pakem dan wilayah tetap mungkin terjadi.',
        },
        {
            heading: 'Referensi eksternal',
            body: 'Sebagian hasil dibandingkan dengan Kalender Bali Digital (kalenderbali.org). Wariga Bali Santih tidak berafiliasi dengan KalenderBali.org dan tidak menyatakan referensi tersebut sebagai satu-satunya otoritas.',
        },
        {
            heading: 'Pembelajaran pengguna',
            body: 'Penilaian pengguna hanya menjadi bahan analisis agregat. Voting, pengalaman pribadi, atau usulan mesin tidak otomatis mengubah aturan sebelum ditinjau validator.',
        },
    ],
};

export function PlaceholderScreen({ description, title }: Props) {
    const sections = content[title] ?? [{ heading: title, body: description }];

    return (
        <ScreenContainer style={{ gap: spacing.lg }}>
            <AppText variant="title" weight="bold">
                {title}
            </AppText>
            <AppText color="textMuted">{description}</AppText>
            {sections.map(section => (
                <AppCard key={section.heading} style={{ gap: spacing.sm }}>
                    <AppText variant="titleSmall" weight="semibold">
                        {section.heading}
                    </AppText>
                    <AppText color="textMuted" variant="bodySmall">
                        {section.body}
                    </AppText>
                </AppCard>
            ))}
        </ScreenContainer>
    );
}
