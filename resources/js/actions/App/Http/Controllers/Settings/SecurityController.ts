import { route } from '@/lib/route';
export const edit = route('/settings/security', 'get');
export const update = route('/settings/password', 'put');
export default { edit, update };
