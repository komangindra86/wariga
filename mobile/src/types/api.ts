export interface ApiResponse<T> {
    success: true;
    message: string;
    data: T;
    meta?: PaginationMeta;
}

export type ApiErrorCode =
    | 'OFFLINE'
    | 'TIMEOUT'
    | 'UNAUTHENTICATED'
    | 'FORBIDDEN'
    | 'NOT_FOUND'
    | 'VALIDATION'
    | 'RATE_LIMITED'
    | 'SERVER_ERROR'
    | 'MAINTENANCE'
    | 'UNKNOWN';

export interface ApiError extends Error {
    name: 'ApiError';
    code: ApiErrorCode;
    status?: number;
    fields?: Record<string, string[]>;
    retryAfter?: number;
}

export interface ApiErrorPayload {
    success: false;
    message: string;
    errors?: Record<string, string[]>;
    error_code?: string;
}

export interface PaginationMeta {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}
