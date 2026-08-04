import Config from 'react-native-config';

const parsedTimeout = Number(Config.API_TIMEOUT);

if (!Config.API_BASE_URL) {
    throw new Error('API_BASE_URL wajib dikonfigurasi.');
}

if (!Number.isFinite(parsedTimeout) || parsedTimeout <= 0) {
    throw new Error('API_TIMEOUT harus berupa angka positif.');
}

export const environment = Object.freeze({
    apiBaseUrl: Config.API_BASE_URL,
    apiHostHeader: Config.API_HOST_HEADER,
    apiTimeout: parsedTimeout,
    appEnv: Config.APP_ENV,
    donationUrl: Config.DONATION_URL?.trim() || null,
});
