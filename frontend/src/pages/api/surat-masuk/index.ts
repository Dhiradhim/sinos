import type { APIRoute } from 'astro';
import { getSession } from '../../../lib/session';
import { errorFields, submitSuratMasuk } from '../../../lib/suratMasuk';
import { setFormFeedback } from '../../../lib/formFeedback';

/**
 * Action route: simpan surat masuk baru.
 *
 * Form HTML mengirim POST multipart ke sini; route meneruskan ke
 * `POST /api/v1/surat-masuk` dengan Bearer dari cookie httpOnly. Browser tidak
 * pernah melihat token.
 *
 * Hasil:
 *   201 → 303 ke /surat-masuk/daftar?tahun={tahun tgl_surat}
 *   422 → 303 kembali ke /surat-masuk/input, pesan + nilai disimpan di cookie
 *   401 → 303 ke /login
 *   lain → 303 kembali ke form dengan pesan umum
 */
export const POST: APIRoute = async ({ cookies, request, redirect }) => {
  const token = getSession(cookies);
  if (!token) return redirect('/login', 303);

  let form: FormData;
  try {
    form = await request.formData();
  } catch {
    return redirect('/surat-masuk/input', 303);
  }

  const result = await submitSuratMasuk(form, token);

  if (result.status === 401) return redirect('/login', 303);

  if (result.ok && result.data) {
    const tahun = String(result.data.tgl_surat?.iso ?? '').slice(0, 4);
    const target = tahun
      ? `/surat-masuk/daftar?tahun=${tahun}`
      : '/surat-masuk/daftar';
    return redirect(target, 303);
  }

  const values: Record<string, string> = {};
  for (const [key, value] of form.entries()) {
    if (typeof value === 'string' && key !== '_method') values[key] = value;
  }

  setFormFeedback(cookies, {
    errors: errorFields(result.error),
    values,
    message:
      result.error?.message ??
      'Surat masuk gagal disimpan. Silakan coba lagi.',
  });

  return redirect('/surat-masuk/input', 303);
};
