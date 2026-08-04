import { apiClient } from '@/api/client';
import { environment } from '@/config/environment';
import type { ApiResponse } from '@/types/api';
import type {
    BaliCalendarDate,
    AuthSession,
    DewasaDetail,
    DewasaFeedbackPayload,
    DewasaSearchParams,
    DewasaSearchResponse,
    DailyRecommendations,
    SavedDewasa,
    ShareLink,
    User,
    UpcomingCalendarEvent,
    WarigaActivity,
} from '@/types/domain';

type ApiTransportResponse<T> =
    | { data: ApiResponse<T> }
    | ApiResponse<T>
    | { data: T }
    | T;

export function unwrapApiData<T>(response: ApiTransportResponse<T>): T {
    const value = response as Record<string, unknown>;
    const nested = value?.data as Record<string, unknown> | undefined;

    if (nested?.success === true && 'data' in nested) {
        return nested.data as T;
    }
    if (value?.success === true && 'data' in value) {
        return value.data as T;
    }
    if (nested !== undefined) {
        return nested as T;
    }

    return response as T;
}

async function data<T>(request: Promise<ApiTransportResponse<T>>): Promise<T> {
    return unwrapApiData(await request);
}

async function publicGet<T>(path: string): Promise<T> {
    const response = await fetch(`${environment.apiBaseUrl}${path}`, {
        headers: {
            Accept: 'application/json',
            ...(environment.apiHostHeader
                ? { Host: environment.apiHostHeader }
                : {}),
        },
    });
    if (!response.ok) {
        throw new Error(`Layanan mengembalikan status ${response.status}.`);
    }

    return unwrapApiData((await response.json()) as ApiResponse<T>);
}

type CalendarElementPayload = { nama?: string | null; urutan?: number | null };
type CalendarTodayPayload = {
    calendar: {
        tanggal_masehi: string;
        wuku?: CalendarElementPayload | null;
        wewaran?: Record<string, CalendarElementPayload | null>;
    };
    lunar?: {
        available?: boolean;
        sasih?: CalendarElementPayload | null;
        fase?: 'penanggal' | 'pangelong' | string | null;
        nomor_hari?: number | null;
        is_purnama?: boolean;
        is_tilem?: boolean;
        message?: string | null;
        verification_status?: string;
    };
    verification_status?: string;
    observances?: BaliCalendarDate['observances'];
    is_public_holiday?: boolean;
    is_red_date?: boolean;
};

type PaginatedPayload<T> = {
    items: T[];
};

type DewasaDetailPayload = {
    activity: WarigaActivity;
    recommendations: DewasaDetail[];
    engine_version?: string;
    rule_version?: string;
    dataset_version?: string;
    verification_status?: string;
};

export function mapCalendarDate(
    payload: CalendarTodayPayload,
): BaliCalendarDate {
    const lunar = payload.lunar;
    const phase = lunar?.fase?.toLowerCase();

    return {
        date: payload.calendar.tanggal_masehi,
        wuku: payload.calendar.wuku?.nama ?? null,
        wuku_order: payload.calendar.wuku?.urutan ?? null,
        pancawara:
            payload.calendar.wewaran?.panca_wara?.nama ??
            payload.calendar.wewaran?.pancawara?.nama ??
            null,
        pancawara_order: payload.calendar.wewaran?.panca_wara?.urutan ?? payload.calendar.wewaran?.pancawara?.urutan ?? null,
        triwara:
            payload.calendar.wewaran?.tri_wara?.nama ??
            payload.calendar.wewaran?.triwara?.nama ??
            null,
        triwara_order: payload.calendar.wewaran?.tri_wara?.urutan ?? payload.calendar.wewaran?.triwara?.urutan ?? null,
        saptawara:
            payload.calendar.wewaran?.sapta_wara?.nama ??
            payload.calendar.wewaran?.saptawara?.nama ??
            null,
        saptawara_order: payload.calendar.wewaran?.sapta_wara?.urutan ?? payload.calendar.wewaran?.saptawara?.urutan ?? null,
        sadwara: payload.calendar.wewaran?.sad_wara?.nama ?? payload.calendar.wewaran?.sadwara?.nama ?? null,
        sadwara_order: payload.calendar.wewaran?.sad_wara?.urutan ?? payload.calendar.wewaran?.sadwara?.urutan ?? null,
        sasih: lunar?.sasih?.nama ?? null,
        penanggal: phase === 'penanggal' ? lunar?.nomor_hari : null,
        pangelong: phase === 'pangelong' ? lunar?.nomor_hari : null,
        is_full_moon: lunar?.is_purnama ?? false,
        is_new_moon: lunar?.is_tilem ?? false,
        lunar_available: lunar?.available ?? false,
        lunar_message: lunar?.message ?? null,
        verification_status:
            lunar?.verification_status ??
            payload.verification_status ??
            'unavailable',
        observances: payload.observances ?? [],
        is_public_holiday: payload.is_public_holiday ?? false,
        is_red_date: payload.is_red_date ?? payload.is_public_holiday ?? false,
    };
}

export const endpoints = {
    login: (payload: {
        email: string;
        password: string;
        device_name: string;
    }) => data<AuthSession>(apiClient.post('/auth/login', payload)),
    register: (payload: {
        name: string;
        email: string;
        password: string;
        password_confirmation: string;
    }) => data<AuthSession>(apiClient.post('/auth/register', payload)),
    forgotPassword: (email: string) =>
        data<null>(apiClient.post('/auth/forgot-password', { email })),
    resetPassword: (payload: {
        token: string;
        email: string;
        password: string;
        password_confirmation: string;
    }) => data<null>(apiClient.post('/auth/reset-password', payload)),
    logout: () => data<null>(apiClient.post('/auth/logout')),
    deleteAccount: () => data<null>(apiClient.delete('/user/account')),
    activities: async () =>
        (
            await publicGet<PaginatedPayload<WarigaActivity>>(
                '/activities?per_page=250',
            )
        ).items,
    calendarToday: async () =>
        mapCalendarDate(
            await data<CalendarTodayPayload>(apiClient.get('/calendar/today')),
        ),
    calendarDate: async (date: string) =>
        mapCalendarDate(
            await data<CalendarTodayPayload>(
                apiClient.get(`/calendar/${encodeURIComponent(date)}`),
            ),
        ),
    calendarMonth: async (year: number, month: number) =>
        (
            await data<PaginatedPayload<CalendarTodayPayload>>(
                apiClient.get('/calendar/month', {
                    params: { year, month, per_page: 31 },
                }),
            )
        ).items.map(mapCalendarDate),
    upcomingCalendarEvents: async () =>
        (
            await data<PaginatedPayload<UpcomingCalendarEvent>>(
                apiClient.get('/calendar/upcoming', { params: { days: 90 } }),
            )
        ).items,
    searchDewasa: (params: DewasaSearchParams) =>
        data<DewasaSearchResponse>(apiClient.get('/dewasa/search', { params })),
    dailyRecommendations: () =>
        data<DailyRecommendations>(apiClient.get('/dewasa/today')),
    dewasaDetail: async (activity: string, date: string) => {
        const payload = await data<DewasaDetailPayload>(
            apiClient.get(
                `/dewasa/${encodeURIComponent(activity)}/${encodeURIComponent(
                    date,
                )}`,
            ),
        );
        const recommendation = payload.recommendations[0];
        if (!recommendation) {
            throw new Error('Detail rekomendasi tidak ditemukan.');
        }

        return {
            ...recommendation,
            activity: payload.activity,
            dataset_version: payload.dataset_version,
            engine_version: payload.engine_version,
            rule_version: payload.rule_version,
            rules: recommendation.main_reasons,
            verification_status: payload.verification_status,
        } as DewasaDetail;
    },
    submitDewasaFeedback: (payload: DewasaFeedbackPayload) =>
        data<{ id: number; rating: string; updated: boolean }>(
            apiClient.post('/dewasa/feedback', payload),
        ),
    submitContribution: (payload: {
        type: 'app_feedback' | 'result_report' | 'wariga_knowledge';
        is_anonymous: boolean;
        contact_email?: string;
        subject: string;
        message: string;
        activity_name?: string;
        related_date?: string;
        region?: string;
        tradition?: string;
        source_reference?: string;
        consent: true;
    }) => data<{ id: number; status: string; status_label: string }>(apiClient.post('/contributions', payload)),
    saveDewasa: (payload: {
        activity_id: number;
        date: string;
        notes?: string | null;
    }) => data<SavedDewasa>(apiClient.post('/user/saved-dewasa', payload)),
    user: () => data<User>(apiClient.get('/user')),
    savedDewasa: async () =>
        (
            await data<PaginatedPayload<SavedDewasa>>(
                apiClient.get('/user/saved-dewasa'),
            )
        ).items,
    savedDewasaDetail: (id: number) =>
        data<SavedDewasa>(apiClient.get(`/user/saved-dewasa/${id}`)),
    deleteSavedDewasa: (id: number) =>
        data<null>(apiClient.delete(`/user/saved-dewasa/${id}`)),
    shareSavedDewasa: (savedDewasaId: number) =>
        data<ShareLink>(
            apiClient.post('/dewasa/share', {
                saved_dewasa_id: savedDewasaId,
            }),
        ),
};
