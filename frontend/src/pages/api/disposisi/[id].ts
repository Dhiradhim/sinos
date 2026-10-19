import type { APIRoute } from 'astro';
import { apiFetch } from '../../../lib/api';
import { getSession } from '../../../lib/session';
import { setFormFeedback } from '../../../lib/formFeedback';

/**
 * Action route: hapus disposisi.
 *
 *   POST   /api/disposisi/{id}  (form HTML)   → DELETE /api/v1/disposisi/{id}
 *   DELETE /api/disposisi/{id}  (fetch)       → DELETE /api/v1/disposisi/{id}
 *
 * POST disediakan karena form HTML tidak dapat mengirim DELETE. Ini pola yang
 * sama dengan `_method` pada modul surat masuk, hanya saja di sini konversinya
 * dilakukan di route Astro sehingga klien tidak perlu tahu.
 *
 * Hasil POST:
 *   200 → 303 ke `?kembali=` (default /disposisi)
 *   403/404 → 303 dengan umpan balik
 *   401 → 303 ke /login
 */
function safeReturn(raw: string | null, fallback: string): string {
  const value = (raw ?? '').trim();
  if (value.startsWith('/disposisi')) return value;
  return fallback;
}

function json(payload: unknown, status: number): Response {
  return new Response(JSON.stringify(payload), {
    status,
    headers: {
      'Content-Type': 'application/json; charset=utf-8',
      'Cache-Control': 'no-store',
    },
  });
}

async function remove(id: number, token: string) {
  return apiFetch(`/disposisi/${id}`, { method: 'DELETE', token });
}

export const DELETE: APIRoute = async ({ cookies, params }) => {
  const token = getSession(cookies);
  if (!token) return json({ error: { message: 'Sesi Anda sudah berakhir.' } }, 401);

  const id = Number(params.id);
  if (!Number.isInteger(id) || id <= 0) {
    return json({ error: { message: 'Disposisi tidak ditemukan.' } }, 404);
  }

  const result = await remove(id, token);

  if (!result.ok) {
    return json(
      { error: result.error ?? { message: 'Disposisi gagal dihapus.' } },
      result.status || 502,
    );
  }

  return json({ data: result.data }, 200);
};

export const POST: APIRoute = async ({ cookies, params, request, redirect }) => {
  const token = getSession(cookies);
  if (!token) return redirect('/login', 303);

  const id = Number(params.id);
  if (!Number.isInteger(id) || id <= 0) {
    return redirect('/disposisi', 303);
  }

  const url = new URL(request.url);
  const kembali = safeReturn(url.searchParams.get('kembali'), '/disposisi');

  const result = await remove(id, token);

  if (result.status === 401) return redirect('/login', 303);

  if (!result.ok) {
    setFormFeedback(cookies, {
      target: kembali,
      type: 'error',
      message: result.error?.message ?? 'Disposisi gagal dihapus.',
      code: result.error?.code ?? null,
    });
    return redirect(kembali, 303);
  }

  setFormFeedback(cookies, {
    target: kembali,
    type: 'success',
    message:
      (result.data as { pesan?: string } | null)?.pesan ??
      'Disposisi berhasil dihapus.',
  });

  return redirect(kembali, 303);
};
