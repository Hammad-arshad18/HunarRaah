import { route } from '@/lib/route';
export const notice = route('/email/verify', 'get');
export const verify = route('/email/verify/{id}/{hash}', 'get');
export const send = route('/email/verification-notification', 'post');
export default { notice, verify, send };
