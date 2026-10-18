import type { APIRoute } from 'astro';
import { apiFetch } from '../../../../lib/api';
import { getSession } from '../../../../lib/session';

/**
 * Proxy BFF untuk dialog disposisi pada halaman daftar surat masuk.
 *
 *   GET  /api/surat-masuk/{id}/disposisi  → GET  /api/v1/disposisi/form/{id}
 *   POST /api/surat-masuk/{id}/disposisi  → POST /api/v1/disposisi
 *
 * Token hanya ada di cookie httpOnly; browser menerima JSON bersih
 * (`{ data }` atau `{ error }`) tanpa pernah melihat Bearer.
 *
 * Rute terpisah dari `api/surat-masuk/[id].ts` karena Astro memetakan berkas
 * ke segmen URL, dan `/api/surat-masuk/388/disposisi` harus menjadi rute
 * sendiri agar tidak tertangkap handler update.
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

  const id = Number(params.id);
  if (!Number.isInteger(id) || id <= 0) {
    return json({ error: { message: 'Surat masuk tidak ditemukan.' } }, 404);
  }

  const result = await apiFetch(`/disposisi/form/${id}`, { token });

  if (!result.ok) {
    return json(
      { error: result.error ?? { message: 'Gagal memuat form disposisi.' } },
      result.status || 502,
    );
  }

  return json({ data: result.data }, 200);
};

export const POST: APIRoute = async ({ cookies, params, request }) => {
  const token = getSession(cookies);
  if (!token) return json({ error: { message: 'Sesi Anda sudah berakhir.' } }, 401);

  const id = Number(params.id);
  if (!Number.isInteger(id) || id <= 0) {
    return json({ error: { message: 'Surat masuk tidak ditemukan.' } }, 404);
  }

  let body: Record<string, unknown>;
  try {
    body = (await request.json()) as Record<string, unknown>;
  } catch {
    return json({ error: { message: 'Data disposisi tidak valid.' } }, 400);
  }

  // `surmas_id` diambil dari rute, bukan dari body: satu sumber kebenaran dan
  // tidak dapat dipalsukan klien untuk mengirim ke surat lain.
  const payload = {
    surmas_id: id,
    target: String(body.target ?? '').trim(),
    instruksi: String(body.instruksi ?? '').trim(),
    catatan: String(body.catatan ?? '').trim(),
  };

  const result = await apiFetch('/disposisi', {
    method: 'POST',
    token,
    body: payload,
  });

  if (!result.ok) {
    return json(
      { error: result.error ?? { message: 'Disposisi gagal dikirim.' } },
      result.status || 502,
    );
  }

  return json({ data: result.data }, result.status || 201);
};
