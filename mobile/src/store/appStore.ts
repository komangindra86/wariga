import { create } from 'zustand';

import type { DewasaSearchParams, SearchMode } from '@/types/domain';

interface ResultFilters {
    classification: 'all' | 'very_good' | 'good' | 'neutral' | 'avoid';
    minimumScore: number | null;
}

interface AppState {
    isHydrated: boolean;
    selectedActivity: string | null;
    dateRange: { startDate: string | null; endDate: string | null };
    searchMode: SearchMode;
    searchDraft: Partial<DewasaSearchParams>;
    resultFilters: ResultFilters;
    isExpertExplanationOpen: boolean;
    setHydrated: (isHydrated: boolean) => void;
    setSelectedActivity: (activity: string | null) => void;
    setDateRange: (startDate: string | null, endDate: string | null) => void;
    setSearchMode: (mode: SearchMode) => void;
    updateSearchDraft: (draft: Partial<DewasaSearchParams>) => void;
    setResultFilters: (filters: Partial<ResultFilters>) => void;
    setExpertExplanationOpen: (isOpen: boolean) => void;
    resetSearch: () => void;
}

export const useAppStore = create<AppState>(set => ({
    isHydrated: false,
    selectedActivity: null,
    dateRange: { startDate: null, endDate: null },
    searchMode: 'simple',
    searchDraft: {},
    resultFilters: { classification: 'all', minimumScore: null },
    isExpertExplanationOpen: false,
    setHydrated: isHydrated => set({ isHydrated }),
    setSelectedActivity: selectedActivity => set({ selectedActivity }),
    setDateRange: (startDate, endDate) =>
        set({ dateRange: { startDate, endDate } }),
    setSearchMode: searchMode => set({ searchMode }),
    updateSearchDraft: draft =>
        set(state => ({ searchDraft: { ...state.searchDraft, ...draft } })),
    setResultFilters: filters =>
        set(state => ({
            resultFilters: { ...state.resultFilters, ...filters },
        })),
    setExpertExplanationOpen: isExpertExplanationOpen =>
        set({ isExpertExplanationOpen }),
    resetSearch: () =>
        set({
            selectedActivity: null,
            dateRange: { startDate: null, endDate: null },
            searchMode: 'simple',
            searchDraft: {},
            resultFilters: { classification: 'all', minimumScore: null },
        }),
}));
