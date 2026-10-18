import type { APIRoute } from 'astro';
import { apiFetch } from '../../../../lib/api';
import { getSession } from '../../../../lib/session';

/**
 * Proxy BFF: data form disposisi (JSON passthrough).
 *
 *   GET /api/disposisi/form/{surmas_id}  →  GET /api/v1/disposisi/form/{surmas_id}
 *
 * Menggantikan `Disposisi::form_ajax()` milik CI3 yang mengembalikan fragmen
 * HTML. Di sini yang dikirim ke browser tetap JSON; `DisposisiModal.astro`
 * yang merakit markupnya.
 */
function json(payload: unknown, status: number): Response {
  return new Response(JSON.stringify(payload), {
    status,
    headers: {
      'Content-Type': 'application/json; charset=utf-8',
      'Cache-Control': 'no-store',
    },
  });
}

export const GET: APIRoute = async ({ cookies, params }) => {
  const token = getSession(cookies);
  if (!token) return json({ error: { message: 'Sesi Anda sudah berakhir.' } }, 401);

  const surmasId = Number(params.surmas_id);
  if (!Number.isInteger(surmasId) || surmasId <= 0) {
    return json({ error: { message: 'Surat masuk tidak ditemukan.' } }, 404);
  }

  const result = await apiFetch(`/disposisi/form/${surmasId}`, { token });

  if (!result.ok) {
    return json(
      { error: result.error ?? { message: 'Gagal memuat form disposisi.' } },
      result.status || 502,
    );
  }

  return json({ data: result.data }, 200);
};
