import NetInfo from '@react-native-community/netinfo';
import axios, {
    type AxiosError,
    type AxiosInstance,
    type InternalAxiosRequestConfig,
} from 'axios';

import { notifySessionExpired } from '@/services/authSessionEvents';
import { tokenStorage } from '@/services/tokenStorage';
import type { ApiError, ApiErrorCode, ApiErrorPayload } from '@/types/api';

const statusCodes: Record<number, ApiErrorCode> = {
    401: 'UNAUTHENTICATED',
    403: 'FORBIDDEN',
    404: 'NOT_FOUND',
    422: 'VALIDATION',
    429: 'RATE_LIMITED',
    500: 'SERVER_ERROR',
    503: 'MAINTENANCE',
};

const messages: Record<ApiErrorCode, string> = {
    OFFLINE: 'Tidak ada koneksi internet. Periksa koneksi Anda lalu coba lagi.',
    TIMEOUT: 'Permintaan memerlukan waktu terlalu lama. Silakan coba lagi.',
    UNAUTHENTICATED: 'Sesi Anda telah berakhir. Silakan masuk kembali.',
    FORBIDDEN: 'Anda tidak memiliki izin untuk melakukan tindakan ini.',
    NOT_FOUND: 'Data yang Anda cari tidak ditemukan.',
    VALIDATION: 'Beberapa data belum benar. Periksa kembali isian Anda.',
    RATE_LIMITED: 'Terlalu banyak permintaan. Tunggu sebentar lalu coba lagi.',
    SERVER_ERROR: 'Layanan sedang mengalami gangguan. Silakan coba lagi.',
    MAINTENANCE: 'Layanan sedang dalam pemeliharaan. Silakan coba lagi nanti.',
    UNKNOWN: 'Terjadi kendala. Silakan coba lagi.',
};

export function createApiError(
    code: ApiErrorCode,
    options: Partial<Omit<ApiError, 'code' | 'message'>> = {},
): ApiError {
    return {
        name: 'ApiError',
        code,
        message: messages[code],
        ...options,
    };
}

export function normalizeApiError(error: unknown): ApiError {
    if (isApiError(error)) {
        return error;
    }

    if (!axios.isAxiosError<ApiErrorPayload>(error)) {
        return createApiError('UNKNOWN');
    }

    const axiosError = error as AxiosError<ApiErrorPayload>;
    if (axiosError.code === 'ECONNABORTED' || axiosError.code === 'ETIMEDOUT') {
        return createApiError('TIMEOUT');
    }

    const status = axiosError.response?.status;
    const code = status ? statusCodes[status] ?? 'UNKNOWN' : 'OFFLINE';

    return createApiError(code, {
        status,
        fields: axiosError.response?.data?.errors,
        retryAfter: readRetryAfter(axiosError),
    });
}

export function isApiError(error: unknown): error is ApiError {
    return (
        typeof error === 'object' &&
        error !== null &&
        'name' in error &&
        error.name === 'ApiError' &&
        'code' in error
    );
}

async function addAuthentication(
    config: InternalAxiosRequestConfig,
): Promise<InternalAxiosRequestConfig> {
    const network = await NetInfo.fetch();
    if (
        network.isConnected === false ||
        network.isInternetReachable === false
    ) {
        return Promise.reject(createApiError('OFFLINE'));
    }

    const token = await tokenStorage.get();
    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }

    return config;
}

async function handleError(error: unknown): Promise<never> {
    const apiError = normalizeApiError(error);
    if (apiError.code === 'UNAUTHENTICATED') {
        await tokenStorage.remove();
        notifySessionExpired();
    }

    return Promise.reject(apiError);
}

function readRetryAfter(error: AxiosError): number | undefined {
    const value = error.response?.headers['retry-after'];
    const seconds = Number(value);
    return Number.isFinite(seconds) ? seconds : undefined;
}

export function attachInterceptors(client: AxiosInstance): void {
    client.interceptors.request.use(addAuthentication);
    client.interceptors.response.use(response => response, handleError);
}
