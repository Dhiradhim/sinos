import type { APIRoute } from 'astro';
import { apiFetch } from '../../../lib/api';
import { setSession } from '../../../lib/session';

type ExchangePayload = {
  token: string;
  expires_in: number;
  user: { nip: string; nama: string; is_admin: boolean; is_operator: boolean };
};

/**
 * Callback SSO. Tiket ditukar ke CI3 di sisi server; browser tidak pernah
 * melihat token — hanya cookie httpOnly `sinos_sess` yang ditulis di sini.
 */
export const GET: APIRoute = async ({ cookies, url, request }) => {
  const ticket = url.searchParams.get('ticket') ?? '';
  const state = url.searchParams.get('state') ?? '';
  const expectedState = cookies.get('sinos_oauth_state')?.value ?? '';

  cookies.delete('sinos_oauth_state', { path: '/' });

  // Gateway bisa mengirim error eksplisit (mis. redirect_uri ditolak).
  const gatewayError = url.searchParams.get('error');
  if (gatewayError) {
    return redirect(`/login?error=sso&reason=${encodeURIComponent(gatewayError)}`);
  }

  if (ticket === '' || expectedState === '' || state !== expectedState) {
    return redirect('/login?error=sso&reason=state');
  }

  const result = await apiFetch<ExchangePayload>('/auth/exchange', {
    method: 'POST',
    body: { ticket },
  });

  if (!result.ok || !result.data?.token) {
    return redirect('/login?error=sso&reason=exchange');
  }

  setSession(cookies, result.data.token, result.data.expires_in || 28800, url, request);

  return redirect('/dashboard');
};

function redirect(location: string): Response {
  return new Response(null, {
    status: 302,
    headers: { Location: location, 'Cache-Control': 'no-store' },
  });
}
