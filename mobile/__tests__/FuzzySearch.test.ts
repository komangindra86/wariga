import { activitySearchAliases, categorySearchAliases, FUZZY_SEARCH_THRESHOLD, fuzzySearchScore, normalizeSearchText } from '@/utils/fuzzySearch';

describe('pencarian kegiatan berdasarkan kemiripan', () => {
    it('menormalkan tanda baca dan kapitalisasi', () => {
        expect(normalizeSearchText('  Tani & Kebun  ')).toBe('tani kebun');
    });

    it('menemukan salah ketik dan variasi kata', () => {
        expect(fuzzySearchScore('ngabn', ['Ngaben'])).toBeGreaterThanOrEqual(FUZZY_SEARCH_THRESHOLD);
        expect(fuzzySearchScore('nikah', ['Upacara Pernikahan'])).toBeGreaterThanOrEqual(FUZZY_SEARCH_THRESHOLD);
        expect(fuzzySearchScore('pindah rmh', ['Pindah Rumah'])).toBeGreaterThanOrEqual(FUZZY_SEARCH_THRESHOLD);
    });

    it('menolak kata yang tidak berkaitan', () => {
        expect(fuzzySearchScore('pesawat', ['Ngaben', 'Upacara & Yadnya'])).toBeLessThan(FUZZY_SEARCH_THRESHOLD);
        expect(fuzzySearchScore('nikah', ['Mencari Nafkah'])).toBeLessThan(FUZZY_SEARCH_THRESHOLD);
    });

    it('memahami nama umum untuk istilah kegiatan Bali', () => {
        const aliases = activitySearchAliases('Pawiwahan');
        expect(fuzzySearchScore('nikah', ['Pawiwahan', aliases])).toBe(1);
        expect(fuzzySearchScore('ngaben', ['Pitra Yadnya', activitySearchAliases('Pitra Yadnya')])).toBe(1);
    });

    it('memahami istilah umum berdasarkan kelompok kegiatan', () => {
        const aliases = categorySearchAliases('Tani & Kebun');
        expect(fuzzySearchScore('berkebun', ['Bercocok Tanam', aliases])).toBe(1);
        expect(fuzzySearchScore('bertani', ['Menanam Padi', aliases])).toBe(1);
    });

    it('mengarahkan istilah mancing hanya ke kegiatan ikan yang relevan', () => {
        expect(fuzzySearchScore('mancing', ['Menangkap Ikan', activitySearchAliases('Menangkap Ikan')])).toBe(1);
        expect(fuzzySearchScore('mancing', ['Mencari Burung', activitySearchAliases('Mencari Burung')])).toBeLessThan(FUZZY_SEARCH_THRESHOLD);
        expect(fuzzySearchScore('mancing', ['Memelihara Ternak', activitySearchAliases('Memelihara Ternak')])).toBeLessThan(FUZZY_SEARCH_THRESHOLD);
    });
});
