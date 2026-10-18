import type { APIRoute } from 'astro';
import { appId, cookieOptions, gatewayBase, redirectUri } from '../../../lib/sso';

/**
 * Mulai alur SSO: bangun URL /authorize gateway lalu redirect.
 *
 * Gateway mensyaratkan app_id, redirect_uri (harus terdaftar di
 * sso_client.redirect_uris), dan state. `state` disimpan di cookie singkat
 * agar callback dapat memverifikasinya (proteksi CSRF).
 *
 * redirect_uri dihitung dari SINOS_PUBLIC_ORIGIN bila diisi, sehingga dev
 * (localhost:4321) dan produksi (domain publik di belakang proxy) memakai
 * nilai yang benar tanpa mengubah kode.
 */
export const GET: APIRoute = async ({ cookies, url, request }) => {
  const state = crypto.randomUUID().replace(/-/g, '');
  const callback = redirectUri(url, request);

  cookies.set('sinos_oauth_state', state, cookieOptions(url, request, 600));

  const authorize = new URL(`${gatewayBase()}/authorize`);
  authorize.searchParams.set('app_id', appId());
  authorize.searchParams.set('redirect_uri', callback);
  authorize.searchParams.set('state', state);

  return new Response(null, {
    status: 302,
    headers: { Location: authorize.toString(), 'Cache-Control': 'no-store' },
  });
};
