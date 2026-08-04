export function normalizeSearchText(value: string) {
    return value
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLocaleLowerCase('id')
        .replace(/[^a-z0-9]+/g, ' ')
        .trim();
}

export const FUZZY_SEARCH_THRESHOLD = 0.75;

const ACTIVITY_ALIASES: Record<string, string> = {
    pawiwahan: 'nikah pernikahan kawin perkawinan menikah',
    'pitra yadnya': 'ngaben kremasi pelebon',
    'manusa yadnya potong gigi': 'mepandes metatah potong gigi',
    'upacara melaspas': 'melaspas penyucian bangunan rumah',
    'menangkap ikan': 'mancing memancing pancing mencari ikan menangkap ikan',
    'membuat alat penagkap ikan': 'alat pancing kail perangkap ikan',
    'membuat bubu seser jaring': 'jaring ikan bubu seser alat tangkap ikan',
};

const CATEGORY_ALIASES: Record<string, string> = {
    'aktivitas sehari hari': 'kegiatan umum kerja bepergian perjalanan usaha kehidupan sehari hari',
    'rumah bangunan': 'membangun renovasi konstruksi pindah rumah',
    'peralatan keterampilan': 'alat kerajinan membuat mengasah senjata',
    'tani kebun': 'berkebun kebun bertani pertanian bercocok tanam menanam tanaman',
    'ternak perairan': 'beternak peternakan ikan perikanan laut kolam',
    'upacara yadnya': 'sembahyang agama ritual yadnya upacara',
};

export function activitySearchAliases(activityName: string) {
    const normalizedName = normalizeSearchText(activityName);
    const exact = ACTIVITY_ALIASES[normalizedName];
    if (exact) return exact;
    const matchingKey = Object.keys(ACTIVITY_ALIASES).find(key => normalizedName.includes(key));
    return matchingKey ? ACTIVITY_ALIASES[matchingKey] : '';
}

export function categorySearchAliases(category?: string | null) {
    return category ? CATEGORY_ALIASES[normalizeSearchText(category)] ?? '' : '';
}

function editSimilarity(left: string, right: string) {
    if (left === right) return 1;
    if (!left.length || !right.length) return 0;
    const previous = Array.from({ length: right.length + 1 }, (_, index) => index);
    for (let row = 1; row <= left.length; row += 1) {
        const current = [row];
        for (let column = 1; column <= right.length; column += 1) {
            current[column] = Math.min(
                current[column - 1] + 1,
                previous[column] + 1,
                previous[column - 1] + (left[row - 1] === right[column - 1] ? 0 : 1),
            );
        }
        previous.splice(0, previous.length, ...current);
    }
    return 1 - previous[right.length] / Math.max(left.length, right.length);
}

export function fuzzySearchScore(queryValue: string, searchableValues: string[]) {
    const query = normalizeSearchText(queryValue);
    if (!query) return 1;
    const text = normalizeSearchText(searchableValues.join(' '));
    if (text.includes(query)) return 1;

    const queryWords = query.split(' ');
    const words = text.split(' ').filter(Boolean);
    const wordScores = queryWords.map(queryWord => {
        const candidates = words.map(word => {
            if (word.startsWith(queryWord) || queryWord.startsWith(word)) return 0.92;
            return editSimilarity(queryWord, word);
        });
        return Math.max(0, ...candidates);
    });

    return wordScores.reduce((sum, score) => sum + score, 0) / wordScores.length;
}
