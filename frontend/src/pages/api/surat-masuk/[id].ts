import type { APIRoute } from 'astro';
import { getSession } from '../../../lib/session';
import { errorFields, submitSuratMasuk } from '../../../lib/suratMasuk';
import { setFormFeedback } from '../../../lib/formFeedback';

/**
 * Action route: simpan perubahan surat masuk.
 *
 * `submitSuratMasuk()` mengirim POST + `_method=PUT`, karena PHP tidak mengisi
 * `$_FILES` pada request PUT. API memperlakukan keduanya sama
 * (`Surat_masuk::method_efektif()`).
 *
 * Hasil:
 *   200 → 303 ke /surat-masuk/detail/{id}
 *   422 → 303 kembali ke /surat-masuk/edit/{id} dengan pesan + nilai
 *   401 → 303 ke /login
 */
export const POST: APIRoute = async ({ cookies, request, params, redirect }) => {
  const token = getSession(cookies);
  const id = String(params.id ?? '').trim();
  if (!token) return redirect('/login', 303);
  if (id === '') return redirect('/surat-masuk/daftar', 303);

  let form: FormData;
  try {
    form = await request.formData();
  } catch {
    return redirect(`/surat-masuk/edit/${id}`, 303);
  }

  const result = await submitSuratMasuk(form, token, id);

  if (result.status === 401) return redirect('/login', 303);

  if (result.ok) {
    return redirect(`/surat-masuk/detail/${id}`, 303);
  }

  const values: Record<string, string> = {};
  for (const [key, value] of form.entries()) {
    if (typeof value === 'string' && key !== '_method') values[key] = value;
  }

  setFormFeedback(cookies, {
    target: `/surat-masuk/edit/${id}`,
    type: 'error',
    errors: errorFields(result.error),
    values,
    message:
      result.error?.message ??
      'Perubahan surat masuk gagal disimpan. Silakan coba lagi.',
  });

  return redirect(`/surat-masuk/edit/${id}`, 303);
};
