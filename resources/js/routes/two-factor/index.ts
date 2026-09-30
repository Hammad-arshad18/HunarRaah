import { route } from '@/lib/route';
export const login = route('/two-factor-challenge', 'get');
export const enable = route('/user/two-factor-authentication', 'post');
export const confirm = route(
    '/user/confirmed-two-factor-authentication',
    'post',
);
export const disable = route('/user/two-factor-authentication', 'delete');
export const qrCode = route('/user/two-factor-qr-code', 'get');
export const secretKey = route('/user/two-factor-secret-key', 'get');
export const recoveryCodes = route('/user/two-factor-recovery-codes', 'get');
export const regenerateRecoveryCodes = route(
    '/user/two-factor-recovery-codes',
    'post',
);
export default {
    login,
    enable,
    confirm,
    disable,
    qrCode,
    secretKey,
    recoveryCodes,
    regenerateRecoveryCodes,
};
