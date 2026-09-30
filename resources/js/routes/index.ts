import { route } from '@/lib/route';
export const login = route('/login', 'get');
export const logout = route('/logout', 'post');
export const register = route('/register', 'get');
export const home = route('/', 'get');
export const dashboard = route('/dashboard', 'get');
