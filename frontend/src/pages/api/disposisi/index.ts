import type { APIRoute } from 'astro';
import { apiFetch } from '../../../lib/api';
import { getSession } from '../../../lib/session';
import { setFormFeedback } from '../../../lib/formFeedback';

/**
 * Proxy BFF: kirim disposisi.
 *
 *   POST /api/disposisi  →  POST /api/v1/disposisi
 *
 * Dua bentuk permintaan dilayani karena halaman memakai keduanya:
 *
 *   1. **JSON** (dari `DisposisiModal.astro`, fetch `application/json`):
 *      respons diteruskan apa adanya sebagai JSON `{ data }` / `{ error }`,
 *      sehingga modal dapat menampilkan pesan 422 per field tanpa reload.
 *   2. **Form HTML** (`application/x-www-form-urlencoded` atau multipart,
 *      mis. tombol "Teruskan Disposisi" tanpa JS): hasilnya diikuti
 *      pola Post/Redirect/Get — 303 ke `?kembali=` (divalidasi) dengan
 *      umpan balik satu kali pakai lewat `setFormFeedback()`.
 *
 * Nama field yang diteruskan persis kontrak API: `surmas_id`, `target`,
 * `instruksi`, `catatan`.
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

/** Hanya path relatif milik modul disposisi; mencegah open redirect. */
function safeReturn(raw: string | null, fallback: string): string {
  const value = (raw ?? '').trim();
  if (value.startsWith('/disposisi')) return value;
  return fallback;
}

export const POST: APIRoute = async ({ cookies, request, redirect }) => {
  const token = getSession(cookies);
  if (!token) {
    const wantsJson = (request.headers.get('accept') ?? '').includes('application/json');
    return wantsJson
      ? json({ error: { message: 'Sesi Anda sudah berakhir.' } }, 401)
      : redirect('/login', 303);
  }

  const contentType = (request.headers.get('content-type') ?? '').toLowerCase();
  const isJson = contentType.includes('application/json');

  let body: Record<string, unknown>;
  let values: Record<string, string> = {};

  if (isJson) {
    try {
      body = (await request.json()) as Record<string, unknown>;
    } catch {
      return json({ error: { message: 'Data disposisi tidak valid.' } }, 400);
    }
  } else {
    let form: FormData;
    try {
      form = await request.formData();
    } catch {
      return redirect('/disposisi', 303);
    }
    body = {};
    for (const [key, value] of form.entries()) {
      if (typeof value !== 'string') continue;
      body[key] = value;
      values[key] = value;
    }
  }

  // `surmas_id` wajib angka > 0; validasi teks selebihnya diserahkan ke API
  // agar pesan 422-nya identik dengan CI3.
  const surmasId = Number(body.surmas_id);
  if (!Number.isInteger(surmasId) || surmasId <= 0) {
    const message = 'Surat masuk tidak ditemukan.';
    if (isJson) return json({ error: { code: 'NOT_FOUND', message } }, 404);
    setFormFeedback(cookies, { message });
    return redirect('/disposisi', 303);
  }

  const payload = {
    surmas_id: surmasId,
    target: String(body.target ?? '').trim(),
    instruksi: String(body.instruksi ?? '').trim(),
    catatan: String(body.catatan ?? '').trim(),
  };

  const result = await apiFetch('/disposisi', {
    method: 'POST',
    token,
    body: payload,
  });

  if (isJson) {
    if (result.status === 401) {
      return json({ error: { message: 'Sesi Anda sudah berakhir.' } }, 401);
    }
    if (!result.ok) {
      return json(
        { error: result.error ?? { message: 'Disposisi gagal dikirim.' } },
        result.status || 502,
      );
    }
    return json({ data: result.data, meta: result.meta }, result.status || 201);
  }

  // Jalur form HTML.
  if (result.status === 401) return redirect('/login', 303);

  const url = new URL(request.url);
  const kembali = safeReturn(url.searchParams.get('kembali'), '/disposisi');

  if (!result.ok) {
    setFormFeedback(cookies, {
      errors: result.error?.fields ?? null,
      values,
      message: result.error?.message ?? 'Disposisi gagal dikirim.',
      code: result.error?.code ?? null,
    });
    return redirect(kembali, 303);
  }

  setFormFeedback(cookies, {
    message:
      (result.data as { pesan?: string } | null)?.pesan ??
      'Disposisi berhasil dikirim.',
  });

  return redirect(kembali, 303);
};
