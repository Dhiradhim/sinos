import type { APIRoute } from 'astro';
import { apiFetch } from '../../../../lib/api';
import { getSession } from '../../../../lib/session';
import { setFormFeedback } from '../../../../lib/formFeedback';

/**
 * Action route: batalkan arsip surat masuk.
 *
 * Meneruskan ke `POST /api/v1/surat-masuk/{id}/batal-arsip` dengan Bearer dari
 * cookie. Tujuan redirect divalidasi agar hanya path `/surat-masuk…` yang
 * diterima (mencegah open redirect lewat `?kembali=`).
 */
function safeReturn(raw: string | null, fallback: string): string {
  const value = (raw ?? '').trim();
  if (value.startsWith('/surat-masuk')) return value;
  return fallback;
}

export const POST: APIRoute = async ({ cookies, params, request, redirect }) => {
  const token = getSession(cookies);
  if (!token) return redirect('/login', 303);

  const id = Number(params.id);
  if (!Number.isInteger(id) || id <= 0) {
    return redirect('/surat-masuk/daftar', 303);
  }

  const url = new URL(request.url);
  const fallback = `/surat-masuk/detail/${id}`;
  const kembali = safeReturn(url.searchParams.get('kembali'), fallback);

  const result = await apiFetch(`/surat-masuk/${id}/batal-arsip`, {
    method: 'POST',
    token,
  });

  if (result.status === 401) return redirect('/login', 303);

  if (!result.ok) {
    setFormFeedback(cookies, {
      message: result.error?.message ?? 'Status arsip gagal dibatalkan.',
    });
  }

  return redirect(kembali, 303);
};
