import axios from 'axios';

import { attachInterceptors } from '@/api/interceptors';
import { environment } from '@/config/environment';

export const apiClient = axios.create({
    baseURL: environment.apiBaseUrl,
    timeout: environment.apiTimeout,
    headers: {
        Accept: 'application/json',
        ...(environment.apiHostHeader
            ? { Host: environment.apiHostHeader }
            : {}),
    },
});

attachInterceptors(apiClient);
