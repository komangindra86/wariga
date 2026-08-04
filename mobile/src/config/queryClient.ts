import { QueryClient } from '@tanstack/react-query';

import type { ApiError } from '@/types/api';

export const queryClient = new QueryClient({
    defaultOptions: {
        queries: {
            gcTime: 30 * 60_000,
            networkMode: 'online',
            retry: (failureCount, error) => {
                const code = (error as ApiError).code;
                return (
                    ![
                        'UNAUTHENTICATED',
                        'FORBIDDEN',
                        'NOT_FOUND',
                        'VALIDATION',
                    ].includes(code) && failureCount < 2
                );
            },
            staleTime: 5 * 60_000,
        },
        mutations: {
            networkMode: 'online',
            retry: false,
        },
    },
});
