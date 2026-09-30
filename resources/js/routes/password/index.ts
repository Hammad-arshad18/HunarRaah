import { route } from '@/lib/route';
export const request = route('/forgot-password', 'get');
export const reset = route('/reset-password/{token}', 'get');
export const email = route('/forgot-password', 'post');
export const update = route('/reset-password', 'post');
export const confirm = route('/user/confirm-password', 'get');
export const confirmation = route('/user/confirmed-password-status', 'get');
export default { request, reset, email, update, confirm, confirmation };
