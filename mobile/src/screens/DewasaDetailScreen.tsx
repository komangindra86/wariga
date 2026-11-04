import type { NativeStackScreenProps } from '@react-navigation/native-stack';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { format, parseISO } from 'date-fns';
import { id } from 'date-fns/locale';
import { useState } from 'react';
import { Alert, Share, StyleSheet, Switch, View } from 'react-native';

import { endpoints } from '@/api/endpoints';
import { queryKeys } from '@/api/queryKeys';
import {
    AppAccordion,
    AppBadge,
    AppButton,
    AppCard,
    AppErrorState,
    AppInput,
    AppSkeleton,
    AppText,
    ScreenContainer,
} from '@/components';
import { useAuthStore } from '@/store/authStore';
import { colors, spacing } from '@/theme';
import type { DewasaDetail, RecommendationReason } from '@/types/domain';
import type { RootStackParamList } from '@/types/navigation';
import { sasihLabel } from '@/utils/calendar';
import { groupReasons, readableValue } from '@/utils/dewasaDetail';
import { statusPresentation } from '@/utils/dewasaResults';

type Props = NativeStackScreenProps<RootStackParamList, 'DewasaDetail'>;

export function DewasaDetailScreen({ navigation, route }: Props) {
    const { activity, date } = route.params;
    const detail = useQuery({
        queryFn: () => endpoints.dewasaDetail(activity, date),
        queryKey: queryKeys.dewasa.detail(activity, date),
    });

    if (detail.isPending) {
        return <LoadingState />;
    }
    if (detail.isError) {
        return (
            <ScreenContainer>
                <AppErrorState
                    message={detail.error.message}
                    onRetry={() => detail.refetch().catch(() => undefined)}
                />
            </ScreenContainer>
        );
    }

    return (
        <DetailContent
            detail={detail.data}
            onLogin={() => navigation.navigate('Login')}
        />
    );
}

function DetailContent({
    detail,
    onLogin,
}: {
    detail: DewasaDetail;
    onLogin: () => void;
}) {
    const queryClient = useQueryClient();
    const isAuthenticated = useAuthStore(state => state.isAuthenticated);
    const badge = statusPresentation(detail);
    const reasons = detail.rules ?? detail.main_reasons;
    const grouped = groupReasons(reasons);
    const calendar = detail.calendar as Record<string, any> | undefined;
    const [feedbackRating, setFeedbackRating] = useState<
        'helpful' | 'not_helpful' | 'unsure' | null
    >(null);
    const [feedbackReason, setFeedbackReason] = useState('');
    const [region, setRegion] = useState('');
    const [tradition, setTradition] = useState('');
    const [consultedExpert, setConsultedExpert] = useState(false);
    const [expertRole, setExpertRole] = useState('');

    const save = useMutation({
        mutationFn: () =>
            endpoints.saveDewasa({
                activity_id: detail.activity.id,
                date: detail.date,
            }),
        onSuccess: async () => {
            await queryClient.invalidateQueries({
                queryKey: queryKeys.saved.all,
            });
            Alert.alert(
                'Tersimpan',
                'Rekomendasi berhasil disimpan ke akun Anda.',
            );
        },
        onError: error => Alert.alert('Simpan gagal', error.message),
    });
    const share = useMutation({
        mutationFn: async () => {
            const saved = await endpoints.saveDewasa({
                activity_id: detail.activity.id,
                date: detail.date,
            });
            const link = await endpoints.shareSavedDewasa(saved.id);
            return { link, saved };
        },
        onSuccess: async ({ link, saved }) => {
            await queryClient.invalidateQueries({
                queryKey: queryKeys.saved.all,
            });
            const savedCalendar = saved.result.recommendations?.[0]?.calendar;
            await Share.share({
                message: [
                    'Hari Baik (Dewasa Ayu)',
                    '',
                    `Kegiatan: ${detail.activity.name}`,
                    `Tanggal: ${dateLabel(detail.date)}`,
                    `Kalender Bali: ${
                        savedCalendar?.wewaran?.sapta_wara?.nama ??
                        savedCalendar?.wewaran?.saptawara?.nama ??
                        'Tidak tersedia'
                    } ${
                        savedCalendar?.wewaran?.panca_wara?.nama ??
                        savedCalendar?.wewaran?.pancawara?.nama ??
                        'Tidak tersedia'
                    }`,
                    `Wuku: ${savedCalendar?.wuku?.nama ?? 'Tidak tersedia'}`,
                    `Status: ${badge.label}`,
                    `Nilai: ${detail.score}/100`,
                    '',
                    'Lihat penjelasan:',
                    link.url,
                ].join('\n'),
                title: 'Hari Baik (Dewasa Ayu)',
            });
        },
        onError: error => Alert.alert('Bagikan gagal', error.message),
    });
    const feedback = useMutation({
        mutationFn: () =>
            endpoints.submitDewasaFeedback({
                activity_id: detail.activity.id,
                recommendation_date: detail.date,
                rating: feedbackRating ?? 'unsure',
                reason: feedbackReason || null,
                region: region || null,
                tradition: tradition || null,
                consulted_expert: consultedExpert,
                expert_role: consultedExpert ? expertRole || null : null,
                learning_consent: true,
            }),
        onSuccess: () => {
            Alert.alert(
                'Terima kasih',
                'Masukan disimpan sebagai bahan tinjauan dan tidak langsung mengubah aturan.',
            );
            setFeedbackRating(null);
        },
        onError: error => Alert.alert('Masukan belum tersimpan', error.message),
    });

    const requireAccount = (action: () => void) => {
        if (isAuthenticated) {
            action();
            return;
        }
        Alert.alert(
            'Masuk diperlukan',
            'Masuk untuk menyimpan atau membuat tautan bagikan.',
            [
                { style: 'cancel', text: 'Batal' },
                { onPress: onLogin, text: 'Masuk' },
            ],
        );
    };

    return (
        <View style={styles.screen}>
            <ScreenContainer style={styles.content}>
                <AppText color="textMuted" variant="bodySmall">
                    {detail.activity.category ?? 'Dewasa Ayu'}
                </AppText>
                <AppText variant="title" weight="bold">
                    {detail.activity.name}
                </AppText>
                <AppText color="textMuted">{dateLabel(detail.date)}</AppText>
                <AppCard style={styles.scoreCard}>
                    <View>
                        <AppText color="textMuted" variant="caption">
                            Nilai (dari 100)
                        </AppText>
                        <AppText
                            color="primary"
                            variant="display"
                            weight="bold"
                        >
                            {detail.score}
                        </AppText>
                    </View>
                    <View style={styles.status}>
                        <AppBadge label={badge.label} tone={badge.tone} />
                        <AppText weight="semibold">
                            {detail.classification}
                        </AppText>
                    </View>
                </AppCard>

                {detail.explanation ? (
                    <ExplanationCard explanation={detail.explanation} />
                ) : null}
                {/* Tanggal yang kurang baik: tampilkan dulu alasan untuk menghindarinya. */}
                {detail.status === 'recommended' ? (
                    <>
                        <AppAccordion
                            initiallyOpen={!detail.explanation}
                            title="Unsur baik yang cocok"
                        >
                            <ReasonList
                                empty="Belum ada faktor pendukung yang cocok."
                                reasons={grouped.recommendations}
                            />
                        </AppAccordion>
                        <AppAccordion title="Unsur kurang baik yang cocok">
                            <ReasonList
                                empty="Tidak ada pertimbangan khusus pada tanggal ini."
                                reasons={grouped.considerations}
                            />
                        </AppAccordion>
                    </>
                ) : (
                    <>
                        <AppAccordion
                            initiallyOpen={!detail.explanation}
                            title="Unsur kurang baik yang cocok"
                        >
                            <ReasonList
                                empty="Tidak ada pantangan khusus; nilainya rendah karena kurang faktor pendukung."
                                reasons={grouped.considerations}
                            />
                        </AppAccordion>
                        <AppAccordion title="Unsur baik yang cocok">
                            <ReasonList
                                empty="Belum ada faktor pendukung yang cocok."
                                reasons={grouped.recommendations}
                            />
                        </AppAccordion>
                    </>
                )}
                <AppAccordion title="Apa itu alahing dewasa?">
                    <AppText variant="bodySmall">
                        Dalam wariga, satu tanggal bisa punya unsur baik dan
                        buruk sekaligus. Yang menentukan adalah unsur yang
                        paling kuat. Urutannya dari yang paling kuat: sasih
                        (bulan Bali), lalu penanggal/pangelong (hitungan hari
                        dalam bulan Bali), lalu wuku (pekan Bali), lalu hari
                        (wewaran).
                    </AppText>
                    <AppText color="textMuted" variant="bodySmall">
                        Rerainan seperti Pagerwesi atau Purnama adalah hari suci
                        untuk sembahyang, tetapi tidak otomatis menjadi hari
                        baik untuk setiap kegiatan.
                    </AppText>
                </AppAccordion>
                <AppAccordion title="Detail Kalender Bali">
                    <DetailRow
                        label="Sapta Wara"
                        value={
                            calendar?.wewaran?.sapta_wara?.nama ??
                            calendar?.wewaran?.saptawara?.nama
                        }
                    />
                    <DetailRow
                        label="Panca Wara"
                        value={
                            calendar?.wewaran?.panca_wara?.nama ??
                            calendar?.wewaran?.pancawara?.nama
                        }
                    />
                    <DetailRow label="Wuku" value={calendar?.wuku?.nama} />
                    <DetailRow label="Sasih" value={sasihLabel(detail.lunar)} />
                    <DetailRow
                        label="Fase"
                        value={detailPhaseLabel(detail.lunar)}
                    />
                </AppAccordion>
                <AppAccordion title="Rujukan dan keandalan">
                    <DetailRow
                        label="Status rujukan"
                        value={verificationLabel(detail.verification_status)}
                    />
                    <DetailRow
                        label="Cara menggunakan hasil"
                        value="Gunakan sebagai panduan memilih tanggal; untuk upacara penting, konfirmasikan kepada pemangku atau ahli Wariga."
                    />
                </AppAccordion>
                <AppAccordion title="Mode Ahli">
                    {reasons.length ? (
                        reasons.map(reason => (
                            <ExpertRule
                                key={`${reason.rule_id}-${reason.rule_code}`}
                                engineVersion={detail.engine_version}
                                reason={reason}
                            />
                        ))
                    ) : (
                        <AppText color="textMuted">
                            Belum ada faktor yang cocok untuk tanggal ini. Pilih
                            tanggal lain untuk melihat dasar penilaiannya.
                        </AppText>
                    )}
                </AppAccordion>
                <AppText color="textMuted" variant="bodySmall">
                    {detail.activity.disclaimer ??
                        'Hasil Wariga adalah panduan budaya dan bukan kepastian mutlak. Untuk upacara penting, konsultasikan dengan sulinggih, pemangku, atau ahli Wariga.'}
                </AppText>
                <AppCard style={styles.feedbackCard}>
                    <AppText variant="titleSmall" weight="bold">
                        Bantu sistem belajar
                    </AppText>
                    <AppText color="textMuted" variant="bodySmall">
                        Apakah rekomendasi ini membantu? Masukan hanya
                        dianalisis secara agregat dan tidak langsung mengubah
                        aturan.
                    </AppText>
                    <View style={styles.feedbackChoices}>
                        <AppButton
                            label="Membantu"
                            onPress={() => setFeedbackRating('helpful')}
                            variant={
                                feedbackRating === 'helpful'
                                    ? 'primary'
                                    : 'outline'
                            }
                        />
                        <AppButton
                            label="Kurang tepat"
                            onPress={() => setFeedbackRating('not_helpful')}
                            variant={
                                feedbackRating === 'not_helpful'
                                    ? 'primary'
                                    : 'outline'
                            }
                        />
                        <AppButton
                            label="Tidak yakin"
                            onPress={() => setFeedbackRating('unsure')}
                            variant={
                                feedbackRating === 'unsure'
                                    ? 'primary'
                                    : 'outline'
                            }
                        />
                    </View>
                    {feedbackRating ? (
                        <View style={styles.feedbackForm}>
                            <AppInput
                                label="Alasan singkat (opsional)"
                                multiline
                                onChangeText={setFeedbackReason}
                                value={feedbackReason}
                            />
                            <AppInput
                                label="Wilayah/desa adat (opsional)"
                                onChangeText={setRegion}
                                value={region}
                            />
                            <AppInput
                                label="Pakem atau tradisi (opsional)"
                                onChangeText={setTradition}
                                value={tradition}
                            />
                            <View style={styles.switchRow}>
                                <AppText variant="bodySmall">
                                    Sudah berkonsultasi dengan ahli
                                    Wariga/pemangku
                                </AppText>
                                <Switch
                                    onValueChange={setConsultedExpert}
                                    value={consultedExpert}
                                />
                            </View>
                            {consultedExpert ? (
                                <AppInput
                                    label="Peran ahli yang dikonsultasikan"
                                    onChangeText={setExpertRole}
                                    placeholder="Contoh: pemangku"
                                    value={expertRole}
                                />
                            ) : null}
                            <AppText color="textMuted" variant="caption">
                                Dengan mengirim, Anda menyetujui penggunaan
                                masukan ini untuk meningkatkan sistem. Identitas
                                tidak ditampilkan pada analisis validator.
                            </AppText>
                            <AppButton
                                fullWidth
                                label={
                                    isAuthenticated
                                        ? 'Kirim masukan'
                                        : 'Masuk untuk mengirim'
                                }
                                loading={feedback.isPending}
                                onPress={() =>
                                    requireAccount(() => feedback.mutate())
                                }
                            />
                        </View>
                    ) : null}
                </AppCard>
            </ScreenContainer>
            <View style={styles.actions}>
                <AppButton
                    fullWidth
                    label="Simpan"
                    loading={save.isPending}
                    onPress={() => requireAccount(() => save.mutate())}
                    variant="outline"
                />
                <AppButton
                    fullWidth
                    label="Bagikan"
                    loading={share.isPending}
                    onPress={() => requireAccount(() => share.mutate())}
                />
            </View>
        </View>
    );
}

function ExplanationCard({
    explanation,
}: {
    explanation: NonNullable<DewasaDetail['explanation']>;
}) {
    return (
        <AppCard style={styles.explanationCard}>
            <AppText color="textMuted" variant="caption">
                Kenapa hasilnya begini?
            </AppText>
            <AppText weight="bold">{explanation.summary}</AppText>
            {explanation.points.map(point => (
                <View key={point} style={styles.point}>
                    <AppText variant="bodySmall">•</AppText>
                    <AppText style={styles.pointText} variant="bodySmall">
                        {point}
                    </AppText>
                </View>
            ))}
        </AppCard>
    );
}

function ReasonList({
    empty,
    reasons,
}: {
    empty: string;
    reasons: RecommendationReason[];
}) {
    if (!reasons.length) {
        return <AppText color="textMuted">{empty}</AppText>;
    }
    return (
        <View style={styles.list}>
            {reasons.map(reason => (
                <View key={reason.rule_id} style={styles.reason}>
                    <AppText weight="semibold">{reason.rule_name}</AppText>
                    <AppText variant="bodySmall">{reason.reason}</AppText>
                    <AppText color="textMuted" variant="caption">
                        {reason.alahing_label
                            ? `${
                                  reason.matched_composition
                                      ? `${reason.matched_composition} · `
                                      : ''
                              }Kekuatan: ${reason.alahing_label}`
                            : reason.score_effect >= 0
                            ? `Menambah nilai ${reason.score_effect}`
                            : `Mengurangi nilai ${Math.abs(
                                  reason.score_effect,
                              )}`}
                    </AppText>
                </View>
            ))}
        </View>
    );
}

function ExpertRule({
    engineVersion,
    reason,
}: {
    engineVersion?: string;
    reason: RecommendationReason;
}) {
    return (
        <View style={styles.expertRule}>
            <AppText color="primary" weight="bold">
                {reason.rule_code} · {reason.rule_name}
            </AppText>
            <DetailRow label="Atribut" value={reason.attribute} />
            <DetailRow label="Operator" value={reason.operator} />
            <DetailRow
                label="Nilai pembanding"
                value={reason.comparison_value}
            />
            <DetailRow label="Nilai tanggal" value={reason.actual_value} />
            <DetailRow
                label="Hasil"
                value={reason.matched ? 'Matched' : 'Tidak matched'}
            />
            <DetailRow label="Dampak skor" value={reason.score_effect} />
            <DetailRow label="Severity" value={reason.severity} />
            <DetailRow
                label="Sumber"
                value={reason.source_name ?? reason.source_reference}
            />
            <DetailRow label="Halaman sumber" value={reason.source_page} />
            <DetailRow
                label="Status verifikasi"
                value={verificationLabel(reason.verification_status)}
            />
            <DetailRow label="Engine version" value={engineVersion} />
            <DetailRow label="Rule version" value={reason.rule_version} />
        </View>
    );
}

function DetailRow({ label, value }: { label: string; value: unknown }) {
    return (
        <View style={styles.row}>
            <AppText
                color="textMuted"
                style={styles.rowLabel}
                variant="caption"
            >
                {label}
            </AppText>
            <AppText style={styles.rowValue} variant="bodySmall">
                {readableValue(value)}
            </AppText>
        </View>
    );
}

function verificationLabel(status?: string) {
    return status === 'verified'
        ? 'Telah ditinjau untuk rujukan aplikasi'
        : status === 'review'
        ? 'Sedang ditinjau'
        : 'Rujukan awal — konsultasi ahli untuk upacara penting';
}

function LoadingState() {
    return (
        <ScreenContainer>
            <View style={styles.list}>
                <AppSkeleton height={34} width="70%" />
                <AppSkeleton height={132} />
                <AppSkeleton height={74} />
                <AppSkeleton height={58} />
                <AppSkeleton height={58} />
            </View>
        </ScreenContainer>
    );
}

function dateLabel(date: string) {
    return format(parseISO(date), 'EEEE, d MMMM yyyy', { locale: id });
}

const styles = StyleSheet.create({
    actions: {
        backgroundColor: colors.surface,
        borderTopColor: colors.border,
        borderTopWidth: StyleSheet.hairlineWidth,
        flexDirection: 'row',
        gap: spacing.md,
        padding: spacing.md,
    },
    content: { gap: spacing.md, paddingBottom: spacing.xxl },
    expertRule: {
        borderBottomColor: colors.border,
        borderBottomWidth: StyleSheet.hairlineWidth,
        gap: spacing.xs,
        paddingVertical: spacing.md,
    },
    feedbackCard: {
        backgroundColor: colors.infoSoft,
        borderColor: colors.infoSoft,
        gap: spacing.md,
    },
    feedbackChoices: {
        flexDirection: 'row',
        flexWrap: 'wrap',
        gap: spacing.sm,
    },
    explanationCard: { gap: spacing.sm },
    feedbackForm: { gap: spacing.md },
    list: { gap: spacing.md },
    point: { flexDirection: 'row', gap: spacing.sm },
    pointText: { flex: 1 },
    reason: { gap: spacing.xs },
    row: { flexDirection: 'row', gap: spacing.md, paddingVertical: spacing.xs },
    rowLabel: { flex: 1 },
    rowValue: { flex: 1.5, textAlign: 'right' },
    scoreCard: {
        alignItems: 'center',
        backgroundColor: colors.primarySoft,
        borderColor: colors.primarySoft,
        flexDirection: 'row',
        justifyContent: 'space-between',
        paddingVertical: spacing.xl,
    },
    screen: { backgroundColor: colors.background, flex: 1 },
    status: { alignItems: 'flex-end', gap: spacing.sm },
    switchRow: {
        alignItems: 'center',
        flexDirection: 'row',
        justifyContent: 'space-between',
    },
});

function detailPhaseLabel(lunar: DewasaDetail['lunar']) {
    if (lunar?.is_purnama) return 'Purnama';
    if (lunar?.is_tilem) return 'Tilem';
    if (!lunar?.fase) return null;
    const fase = lunar.fase === 'pangelong' ? 'Pangelong' : 'Penanggal';
    return lunar.nomor_hari ? `${fase} ${lunar.nomor_hari}` : fase;
}
