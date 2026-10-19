import type { APIRoute } from 'astro';
import { apiFetch } from '../../../lib/api';
import { clearSession, getSession } from '../../../lib/session';
import { ssoLogoutUrl } from '../../../lib/sso';

/**
 * Logout: single-logout ke gateway lewat CI3, hapus cookie sesi Astro, lalu
 * kembalikan browser ke halaman SSO.
 *
 * Tujuan akhirnya adalah endpoint GET /logout milik gateway, bukan /login.
 * Dengan begitu sesi login SSO ikut berakhir (single logout) dan pengguna
 * benar-benar kembali ke halaman SSO, bukan berhenti di halaman transisi
 * SINOS. Kegagalan backend tidak menahan pengguna — cookie tetap dihapus.
 */
async function logout(cookies: Parameters<APIRoute>[0]['cookies']): Promise<Response> {
  const token = getSession(cookies);

  if (token) {
    await apiFetch('/auth/logout', { method: 'POST', token });
  }

  clearSession(cookies);

  return new Response(null, {
    status: 302,
    headers: { Location: ssoLogoutUrl(), 'Cache-Control': 'no-store' },
  });
}

export const POST: APIRoute = ({ cookies }) => logout(cookies);

export const GET: APIRoute = ({ cookies }) => logout(cookies);
