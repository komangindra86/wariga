import * as Keychain from 'react-native-keychain';

const TOKEN_SERVICE = 'com.balisantih.wariga.auth';

export const tokenStorage = {
    async get(): Promise<string | null> {
        const credentials = await Keychain.getGenericPassword({
            service: TOKEN_SERVICE,
        });

        return credentials ? credentials.password : null;
    },

    async remove(): Promise<void> {
        await Keychain.resetGenericPassword({ service: TOKEN_SERVICE });
    },

    async set(token: string): Promise<void> {
        await Keychain.setGenericPassword('token', token, {
            service: TOKEN_SERVICE,
        });
    },
};
