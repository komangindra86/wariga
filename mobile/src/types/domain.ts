export type SearchMode = 'simple' | 'complete' | 'expert';

export interface WarigaActivity {
    id: number;
    code?: string;
    slug: string;
    name: string;
    category?: string | null;
    description?: string | null;
    disclaimer?: string | null;
    icon?: string | null;
    is_popular?: boolean;
}

export interface BaliCalendarDate {
    date: string;
    saka_year?: number | null;
    sasih?: string | null;
    wuku?: string | null;
    wuku_order?: number | null;
    pancawara?: string | null;
    pancawara_order?: number | null;
    triwara?: string | null;
    triwara_order?: number | null;
    saptawara?: string | null;
    saptawara_order?: number | null;
    sadwara?: string | null;
    sadwara_order?: number | null;
    pangelong?: number | null;
    penanggal?: number | null;
    is_full_moon?: boolean;
    is_new_moon?: boolean;
    ceremonies?: string[];
    observances?: CalendarObservance[];
    is_public_holiday?: boolean;
    is_red_date?: boolean;
    lunar_available: boolean;
    lunar_message?: string | null;
    verification_status: string;
}

export interface CalendarObservance {
    name: string;
    type:
        | 'national_holiday'
        | 'hindu_holiday'
        | 'balinese_observance'
        | 'moon_phase';
}

export interface UpcomingCalendarEvent extends CalendarObservance {
    date: string;
    days_until: number;
}

export interface DewasaSearchParams {
    activity: string;
    start_date: string;
    end_date: string;
    mode?: SearchMode;
    sort?: 'ranking' | 'date_asc' | 'date_desc' | 'score_desc';
    limit?: number;
}

export interface DewasaSearchSummary {
    total_dates_checked: number;
    recommended_count: number;
    consideration_count: number;
    avoid_count: number;
}

export interface RecommendationReason {
    rule_id: number;
    rule_code: string;
    rule_name: string;
    rule_type: string;
    severity: string;
    matched: boolean;
    score_effect: number;
    reason: string;
    attribute?: string;
    operator?: string;
    comparison_value?: unknown;
    actual_value?: unknown;
    source_name?: string | null;
    source_reference?: string | null;
    source_page?: string | null;
    verification_status?: string;
    rule_version?: string;
}

export interface DewasaRecommendation {
    date: string;
    score: number;
    classification: string;
    status: 'recommended' | 'consideration' | 'avoid';
    main_reasons: RecommendationReason[];
    calendar?: {
        tanggal_masehi: string;
        wuku?: { nama: string } | null;
        wewaran?: {
            panca_wara?: { nama: string } | null;
            sapta_wara?: { nama: string } | null;
            pancawara?: { nama: string } | null;
            saptawara?: { nama: string } | null;
        };
    };
}

export interface DewasaDetail extends DewasaRecommendation {
    activity: WarigaActivity;
    rules?: RecommendationReason[];
    lunar?: {
        sasih?: { nama?: string } | null;
        fase?: string | null;
        nomor_hari?: number | null;
        is_purnama?: boolean;
        is_tilem?: boolean;
        verification_status?: string;
    };
    engine_version?: string;
    rule_version?: string;
    dataset_version?: string;
    verification_status?: string;
}

export interface DewasaSearchResponse {
    activity: WarigaActivity;
    summary: DewasaSearchSummary;
    recommendations: DewasaRecommendation[];
}

export interface DailyRecommendationItem {
    id: number;
    code?: string | null;
    slug: string;
    name: string;
    category?: string | null;
    icon?: string | null;
    score: number;
    status: 'recommended' | 'avoid';
    reason?: string | null;
    matched_rule?: string | null;
}

export interface DailyRecommendations {
    date: string;
    recommended: DailyRecommendationItem[];
    avoid: DailyRecommendationItem[];
    calendar_basis: {
        saptawara?: string | null;
        pancawara?: string | null;
        wuku?: string | null;
        sasih?: string | null;
    };
    verification_status: string;
}

export interface SavedDewasa {
    id: number;
    activity: WarigaActivity | null;
    date: string;
    notes?: string | null;
    result: DewasaSearchResponse & {
        engine_version?: string;
        rule_version?: string;
        dataset_version?: string;
    };
    score: number;
    status: DewasaRecommendation['status'];
    engine_version: string;
    rule_version: string;
    dataset_version: string;
    snapshot_outdated: boolean;
    snapshot_notice?: string | null;
    created_at: string;
}

export interface ShareLink {
    token: string;
    url: string;
    expires_at?: string | null;
}

export interface User {
    id: number;
    name: string;
    email: string;
    email_verified_at?: string | null;
}

export interface AuthSession {
    token: string;
    user: User;
}

export interface DewasaFeedbackPayload {
    activity_id: number;
    recommendation_date: string;
    rating: 'helpful' | 'not_helpful' | 'unsure';
    reason?: string | null;
    region?: string | null;
    tradition?: string | null;
    consulted_expert?: boolean;
    expert_role?: string | null;
    learning_consent: true;
}
