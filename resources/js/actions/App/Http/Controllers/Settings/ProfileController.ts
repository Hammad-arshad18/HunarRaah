import { route } from '@/lib/route';
export const edit = route('/settings/profile', 'get');
export const update = route('/settings/profile', 'patch');
export const destroy = route('/settings/profile', 'delete');
export default { edit, update, destroy };
