import {
    notifySessionExpired,
    setSessionExpiredHandler,
} from '@/services/authSessionEvents';

describe('Phase 22 authentication session', () => {
    afterEach(() => setSessionExpiredHandler(null));

    it('notifies the active auth state when a token expires', () => {
        const handler = jest.fn();
        setSessionExpiredHandler(handler);

        notifySessionExpired();

        expect(handler).toHaveBeenCalledTimes(1);
    });

    it('allows the session handler to be detached safely', () => {
        const handler = jest.fn();
        setSessionExpiredHandler(handler);
        setSessionExpiredHandler(null);

        notifySessionExpired();

        expect(handler).not.toHaveBeenCalled();
    });
});
