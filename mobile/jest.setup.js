/* global jest */

jest.mock(
    '@react-native-async-storage/async-storage',
    () => {
        const values = new Map();
        return {
            __esModule: true,
            default: {
                clear: jest.fn(async () => values.clear()),
                getAllKeys: jest.fn(async () => Array.from(values.keys())),
                getItem: jest.fn(async key => values.get(key) ?? null),
                removeItem: jest.fn(async key => values.delete(key)),
                setItem: jest.fn(async (key, value) => values.set(key, value)),
            },
        };
    },
);

jest.mock('react-native-config', () => ({
    API_BASE_URL: 'http://localhost/api/v1',
    API_TIMEOUT: '15000',
    APP_ENV: 'development',
}));

jest.mock('@react-native-community/netinfo', () => ({
    __esModule: true,
    default: {
        addEventListener: jest.fn(() => jest.fn()),
        fetch: jest.fn(async () => ({
            isConnected: true,
            isInternetReachable: true,
        })),
    },
}));

jest.mock('@notifee/react-native', () => ({
    __esModule: true,
    AuthorizationStatus: { AUTHORIZED: 1, PROVISIONAL: 2 },
    AndroidImportance: { HIGH: 4 },
    TriggerType: { TIMESTAMP: 0 },
    default: {
        cancelTriggerNotification: jest.fn(async () => undefined),
        createChannel: jest.fn(async () => 'saved-dewasa-reminders'),
        createTriggerNotification: jest.fn(async () => undefined),
        requestPermission: jest.fn(async () => ({ authorizationStatus: 1 })),
    },
}));
