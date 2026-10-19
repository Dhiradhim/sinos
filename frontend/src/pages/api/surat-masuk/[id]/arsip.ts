import type { APIRoute } from 'astro';
import { apiFetch } from '../../../../lib/api';
import { getSession } from '../../../../lib/session';
import { setFormFeedback } from '../../../../lib/formFeedback';

/**
 * Action route: arsipkan surat masuk.
 *
 * Form HTML mengirim POST biasa; route meneruskan ke
 * `POST /api/v1/surat-masuk/{id}/arsip` dengan Bearer dari cookie, lalu
 * mengalihkan (303) kembali ke halaman asal.
 *
 * `?kembali=` menentukan tujuan setelah aksi (detail atau daftar). Nilainya
 * divalidasi: hanya path relatif yang diawali `/surat-masuk` yang diterima,
 * sehingga parameter ini tidak dapat dipakai sebagai open redirect.
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

  const result = await apiFetch(`/surat-masuk/${id}/arsip`, {
    method: 'POST',
    token,
  });

  if (result.status === 401) return redirect('/login', 303);

  if (!result.ok) {
    setFormFeedback(cookies, {
      target: kembali,
      type: 'error',
      message: result.error?.message ?? 'Surat gagal diarsipkan.',
    });
  } else {
    setFormFeedback(cookies, {
      target: kembali,
      type: 'success',
      message: 'Surat berhasil diarsipkan.',
    });
  }

  return redirect(kembali, 303);
};
