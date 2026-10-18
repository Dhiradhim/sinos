import type { APIRoute } from 'astro';
import { apiFetch } from '../../../lib/api';
import { clearSession, getSession } from '../../../lib/session';

/**
 * Logout: single-logout ke gateway lewat CI3, lalu hapus cookie sesi Astro.
 * Kegagalan backend tidak boleh menahan pengguna di aplikasi — cookie tetap
 * dihapus dan pengguna dikembalikan ke halaman login.
 */
async function logout(cookies: Parameters<APIRoute>[0]['cookies']): Promise<Response> {
  const token = getSession(cookies);

  if (token) {
    await apiFetch('/auth/logout', { method: 'POST', token });
  }

  clearSession(cookies);

  return new Response(null, {
    status: 302,
    headers: { Location: '/login', 'Cache-Control': 'no-store' },
  });
}

export const POST: APIRoute = ({ cookies }) => logout(cookies);

export const GET: APIRoute = ({ cookies }) => logout(cookies);
