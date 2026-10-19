import type { APIRoute } from 'astro';
import { apiFetch } from '../../../../lib/api';
import { getSession } from '../../../../lib/session';
import { setFormFeedback } from '../../../../lib/formFeedback';

/**
 * Action route: hapus surat masuk (admin saja).
 *
 * `POST` dipakai karena form HTML tidak dapat mengirim DELETE; API menerima
 * `POST /surat-masuk/{id}/hapus` dan meneruskannya ke `DELETE` yang sama
 * (`Surat_masuk::method_efektif()`). Gerbang admin ditegakkan backend
 * (`Surat_masuk::hapus()`), jadi route ini hanya meneruskan pesan galatnya.
 *
 * Tujuan redirect divalidasi agar hanya path `/surat-masuk…` yang diterima
 * (mencegah open redirect lewat `?kembali=`).
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
  const kembali = safeReturn(
    url.searchParams.get('kembali'),
    '/surat-masuk/daftar',
  );

  const result = await apiFetch(`/surat-masuk/${id}`, {
    method: 'DELETE',
    token,
  });

  if (result.status === 401) return redirect('/login', 303);

  // `target` wajib: cookie ber-path "/" akan terbaca halaman lain bila
  // halaman tujuan (daftar/detail) tidak sempat mengonsumsinya.
  setFormFeedback(cookies, {
    target: kembali,
    type: result.ok ? 'success' : 'error',
    message: result.ok
      ? 'Surat masuk berhasil dihapus.'
      : (result.error?.message ?? 'Surat masuk gagal dihapus.'),
  });

  return redirect(kembali, 303);
};
