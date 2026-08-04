declare module 'react-native-config' {
    export interface NativeConfig {
        readonly API_BASE_URL: string;
        readonly API_HOST_HEADER?: string;
        readonly API_TIMEOUT: string;
        readonly APP_ENV: 'development' | 'staging' | 'production';
        readonly DONATION_URL?: string;
    }

    const Config: NativeConfig;
    export default Config;
}
