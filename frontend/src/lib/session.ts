import { cookieOptions } from './sso';

/**
 * Sesi BFF: token API disimpan di cookie httpOnly milik Astro, bukan cookie PHP.
 * Browser tidak pernah melihat token; hanya server Astro yang membacanya.
 */
export const SESSION_COOKIE = 'sinos_sess';

type CookieJar = {
  set: (
    name: string,
    value: string,
    options: {
      httpOnly?: boolean;
      sameSite?: 'lax' | 'strict' | 'none';
      secure?: boolean;
      path?: string;
      maxAge?: number;
    },
  ) => void;
  delete: (name: string, options?: { path?: string }) => void;
  get: (name: string) => { value: string } | undefined;
};

/**
 * Tulis cookie sesi. maxAge dalam detik (mengikuti expires_in dari API).
 *
 * `secure` ditentukan dari protokol permintaan, bukan `import.meta.env.PROD`.
 * Build produksi yang diuji di http://localhost akan kehilangan cookie bila
 * `secure: true` dipaksa, dan login tampak gagal tanpa error.
 */
export function setSession(
  cookies: CookieJar,
  token: string,
  maxAgeSeconds: number,
  url: URL,
  request: Request,
): void {
  cookies.set(SESSION_COOKIE, token, cookieOptions(url, request, maxAgeSeconds));
}

/** Hapus cookie sesi (logout). Path harus sama dengan saat menulis. */
export function clearSession(cookies: CookieJar): void {
  cookies.delete(SESSION_COOKIE, { path: '/' });
}

/** Token dari cookie, atau null bila belum login. */
export function getSession(cookies: CookieJar): string | null {
  const cookie = cookies.get(SESSION_COOKIE);
  if (!cookie || !cookie.value) return null;
  return cookie.value;
}
