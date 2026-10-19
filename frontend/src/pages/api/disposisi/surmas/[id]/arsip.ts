import type { APIRoute } from 'astro';
import { apiFetch } from '../../../../../lib/api';
import { getSession } from '../../../../../lib/session';
import { setFormFeedback } from '../../../../../lib/formFeedback';

/**
 * Action route: arsipkan surat dari modul disposisi.
 *
 *   POST /api/disposisi/surmas/{surmas_id}/arsip
 *     →  POST /api/v1/disposisi/{surmas_id}/arsip
 *
 * PERHATIAN: parameter rute adalah **ID SURAT MASUK** (`surmas_id`), bukan id
 * disposisi. Ini mencerminkan rute CI3 `disposisi/arsipkan/{surmas_id}` dan
 * `api/Disposisi::arsip($surmas_id)`. Karena itu berkas ini berada di segmen
 * `surmas/` — nama `[id]/arsip.ts` akan menyesatkan pembaca berikutnya.
 *
 * Hasil:
 *   200 → 303 ke `?kembali=` (default /disposisi)
 *   409 → 303 dengan umpan balik "Surat sudah diarsipkan." / posisi bukan operator
 *   401 → 303 ke /login
 *
 * `?kembali=` hanya menerima path relatif milik modul disposisi (open-redirect guard).
 */
function safeReturn(raw: string | null, fallback: string): string {
  const value = (raw ?? '').trim();
  if (value.startsWith('/disposisi')) return value;
  return fallback;
}

export const POST: APIRoute = async ({ cookies, params, request, redirect }) => {
  const token = getSession(cookies);
  if (!token) return redirect('/login', 303);

  const surmasId = Number(params.id);
  if (!Number.isInteger(surmasId) || surmasId <= 0) {
    return redirect('/disposisi', 303);
  }

  const url = new URL(request.url);
  const kembali = safeReturn(url.searchParams.get('kembali'), '/disposisi');

  const result = await apiFetch(`/disposisi/${surmasId}/arsip`, {
    method: 'POST',
    token,
  });

  if (result.status === 401) return redirect('/login', 303);

  if (!result.ok) {
    setFormFeedback(cookies, {
      target: kembali,
      type: 'error',
      message: result.error?.message ?? 'Surat gagal diarsipkan.',
      code: result.error?.code ?? null,
    });
    return redirect(kembali, 303);
  }

  setFormFeedback(cookies, {
    target: kembali,
    type: 'success',
    message:
      (result.data as { pesan?: string } | null)?.pesan ??
      'Surat berhasil diarsipkan.',
  });

  return redirect(kembali, 303);
};
