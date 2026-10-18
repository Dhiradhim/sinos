import type { AstroGlobal } from 'astro';
import { apiFetch } from './api';
import { getSession } from './session';

export type ApiUser = {
  nip: string;
  nama: string;
  is_admin: boolean;
  is_operator: boolean;
  roles?: string[];
  expires_at?: number;
};

/**
 * Identitas user dari token di cookie, atau null bila belum login / token
 * sudah tidak berlaku. Tidak pernah melakukan redirect.
 */
export async function currentUser(Astro: AstroGlobal): Promise<ApiUser | null> {
  const token = getSession(Astro.cookies);
  if (!token) return null;

  const result = await apiFetch<ApiUser>('/auth/me', { token });
  if (!result.ok || !result.data) return null;

  return result.data;
}

/**
 * Hasil requireUser(). Astro hanya mengalihkan bila sebuah `Response`
 * DIKEMBALIKAN dari frontmatter — melemparnya menjadi 500. Karena itu
 * redirect dikembalikan sebagai nilai, bukan exception.
 *
 *   const auth = await requireUser(Astro);
 *   if (auth.redirect) return auth.redirect;
 *   const user = auth.user;
 */
export type RequireUserResult =
  | { user: ApiUser; redirect: null }
  | { user: null; redirect: Response };

/**
 * Seperti currentUser(), tetapi menyiapkan redirect ke /login bila sesi tidak
 * ada atau sudah kedaluwarsa.
 */
export async function requireUser(Astro: AstroGlobal): Promise<RequireUserResult> {
  const user = await currentUser(Astro);
  if (!user) return { user: null, redirect: Astro.redirect('/login') };
  return { user, redirect: null };
}

/** Label peran untuk topbar: Administrator / Operator / Pengguna. */
export function roleLabel(user: Pick<ApiUser, 'is_admin' | 'is_operator'>): string {
  if (user.is_admin) return 'Administrator';
  if (user.is_operator) return 'Operator';
  return 'Pengguna';
}

/** Inisial untuk avatar. */
export function initialOf(nama?: string | null): string {
  const value = (nama ?? '').trim();
  return value ? value.charAt(0).toUpperCase() : 'U';
}
