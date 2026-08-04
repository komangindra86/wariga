import type { NativeStackScreenProps } from '@react-navigation/native-stack';
import { useQuery } from '@tanstack/react-query';
import { useMemo, useState } from 'react';
import { FlatList, Pressable, ScrollView, StyleSheet, View } from 'react-native';

import { endpoints } from '@/api/endpoints';
import { queryKeys } from '@/api/queryKeys';
import { AppCard, AppEmptyState, AppErrorState, AppInput, AppSkeleton, AppText, ScreenContainer } from '@/components';
import { colors, radius, spacing } from '@/theme';
import type { WarigaActivity } from '@/types/domain';
import type { RootStackParamList } from '@/types/navigation';
import { activitySearchAliases, categorySearchAliases, FUZZY_SEARCH_THRESHOLD, fuzzySearchScore } from '@/utils/fuzzySearch';

type Props = NativeStackScreenProps<RootStackParamList, 'ActivityList'>;

export function ActivityListScreen({ navigation }: Props) {
    const [search, setSearch] = useState('');
    const [category, setCategory] = useState('Semua');
    const activities = useQuery({ queryFn: endpoints.activities, queryKey: queryKeys.activities.all });
    const categories = useMemo(() => ['Semua', ...Array.from(new Set((activities.data ?? []).map(item => item.category).filter((item): item is string => Boolean(item)))).sort()], [activities.data]);
    const filtered = useMemo(() => {
        const term = search.trim();
        return (activities.data ?? []).filter(item => category === 'Semua' || item.category === category).map((item, index) => ({
            item, index, score: term ? fuzzySearchScore(term, [item.name, activitySearchAliases(item.name), item.category ?? '', categorySearchAliases(item.category)]) : 1,
        })).filter(result => !term || result.score >= FUZZY_SEARCH_THRESHOLD).sort((left, right) => (right.score - left.score) || (left.index - right.index)).map(result => result.item);
    }, [activities.data, category, search]);

    const header = (
        <View style={styles.header}>
            <View style={styles.intro}><AppText variant="title" weight="bold">Kegiatan apa yang akan dilakukan?</AppText><AppText color="textMuted">Pilih kegiatan, lalu tentukan rentang waktu pencarian.</AppText></View>
            <AppInput accessibilityLabel="Cari kegiatan" helperText="Bisa memakai sebagian kata atau ejaan yang mendekati." onChangeText={setSearch} placeholder="Contoh: ngabn, nikah, pindah rumah" returnKeyType="search" value={search} />
            <ScrollView contentContainerStyle={styles.filters} horizontal showsHorizontalScrollIndicator={false} style={styles.filterScroller}>
                {categories.map(item => <Pressable accessibilityRole="button" key={item} onPress={() => setCategory(item)} style={[styles.filter, category === item && styles.filterSelected]}><AppText color={category === item ? 'textInverse' : 'text'} variant="bodySmall" weight="medium">{item}</AppText></Pressable>)}
            </ScrollView>
            {!activities.isPending && !activities.isError ? <View style={styles.resultSummary}><AppText variant="bodySmall" weight="semibold">{filtered.length} kegiatan</AppText><AppText color="textMuted" variant="caption">Ketuk untuk melanjutkan</AppText></View> : null}
        </View>
    );

    return (
        <ScreenContainer scroll={false} style={styles.screen}>
            <FlatList
                data={activities.isPending || activities.isError ? [] : filtered}
                initialNumToRender={8}
                ItemSeparatorComponent={ListSeparator}
                keyboardShouldPersistTaps="handled"
                keyExtractor={item => String(item.id)}
                ListEmptyComponent={activities.isPending ? <LoadingList /> : activities.isError ? <AppErrorState message={activities.error.message} onRetry={() => activities.refetch().catch(() => undefined)} /> : <AppEmptyState message="Coba kata kunci atau kategori lain." title="Kegiatan tidak ditemukan" />}
                ListHeaderComponent={header}
                maxToRenderPerBatch={8}
                removeClippedSubviews
                renderItem={({ item }) => <ActivityCard item={item} onPress={() => navigation.navigate('DateRange', { activityId: item.id })} />}
                showsVerticalScrollIndicator={false}
                windowSize={7}
            />
        </ScreenContainer>
    );
}

function ActivityCard({ item, onPress }: { item: WarigaActivity; onPress: () => void }) {
    return <Pressable accessibilityHint="Lanjut memilih rentang waktu" accessibilityRole="button" onPress={onPress}>{({ pressed }) => <AppCard style={[styles.card, pressed && styles.pressed]}><View style={styles.compactRow}><View style={styles.iconBubble}><AppText>{item.icon ?? '◇'}</AppText></View><View style={styles.activityTitle}><AppText numberOfLines={2} variant="titleSmall" weight="semibold">{item.name}</AppText>{item.category ? <AppText color="warning" variant="caption" weight="medium">{item.category}</AppText> : null}</View><AppText color="primary" variant="title">›</AppText></View><AppText color="textMuted" numberOfLines={2} variant="bodySmall">{item.description ?? 'Deskripsi kegiatan belum tersedia.'}</AppText></AppCard>}</Pressable>;
}

function LoadingList() { return <View style={styles.loading}><AppSkeleton height={104} /><AppSkeleton height={104} /><AppSkeleton height={104} /></View>; }
function ListSeparator() { return <View style={styles.separator} />; }

const styles = StyleSheet.create({
    activityTitle: { flex: 1, gap: spacing.xs }, card: { gap: spacing.sm }, compactRow: { alignItems: 'center', flexDirection: 'row', gap: spacing.md },
    filter: { backgroundColor: colors.surface, borderColor: colors.border, borderRadius: radius.full, borderWidth: 1, paddingHorizontal: spacing.md, paddingVertical: spacing.sm }, filterScroller: { flexGrow: 0 }, filterSelected: { backgroundColor: colors.primary, borderColor: colors.primary }, filters: { flexDirection: 'row', gap: spacing.sm, paddingRight: spacing.xl },
    header: { gap: spacing.lg, paddingBottom: spacing.lg }, iconBubble: { alignItems: 'center', backgroundColor: colors.primarySoft, borderRadius: radius.full, height: 44, justifyContent: 'center', width: 44 }, intro: { gap: spacing.sm }, loading: { gap: spacing.md }, pressed: { opacity: 0.72 }, resultSummary: { alignItems: 'center', flexDirection: 'row', justifyContent: 'space-between' }, screen: { flex: 1 }, separator: { height: spacing.md },
});
