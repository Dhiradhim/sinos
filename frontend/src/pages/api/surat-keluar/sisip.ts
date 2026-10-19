import type { APIRoute } from "astro";
import { getSession } from "../../../lib/session";
import {
  errorFields,
  nomorList,
  submitSisip,
  summarizeSisipMeta,
} from "../../../lib/suratKeluar";
import { setFormFeedback } from "../../../lib/formFeedback";

/**
 * Action route: sisip nomor surat (tanggal mundur).
 *
 * Meneruskan ke `POST /api/v1/surat-keluar/sisip`. Pada 201, `meta` respons
 * (`last_no_urut_sebelum`, `huruf_terpakai`, `nomor_terpakai`) diringkas dan
 * disimpan di cookie umpan balik sekali pakai, sehingga halaman dapat
 * menampilkan jejak audit setelah redirect 303 — di CI3 informasi ini hanya
 * terlihat lewat flashdata.
 *
 * Hasil:
 *   201 → 303 kembali ke /surat-keluar/sisip dengan `.alert-success` berisi
 *         ringkasan meta (audit trail). DEVIASI dari CI3 (yang mengalihkan ke
 *         daftar): brief meminta ringkasan `meta.last_no_urut_sebelum` /
 *         `nomor_terpakai` / `huruf_terpakai` ditampilkan setelah 201, dan
 *         satu-satunya halaman yang merender ringkasan itu adalah form sisip.
 *         Mengalihkan ke daftar akan membuang cookie umpan balik tanpa pernah
 *         menampilkannya.
 *   422 → 303 kembali ke /surat-keluar/sisip, error per-field + nilai
 *   401 → 303 ke /login
 */
export const POST: APIRoute = async ({ cookies, request, redirect }) => {
  const token = getSession(cookies);
  if (!token) return redirect("/login", 303);

  let form: FormData;
  try {
    form = await request.formData();
  } catch {
    return redirect("/surat-keluar/sisip", 303);
  }

  const result = await submitSisip(form, token);

  if (result.status === 401) return redirect("/login", 303);

  const values: Record<string, string> = {};
  for (const [key, value] of form.entries()) {
    if (typeof value === "string" && key !== "_method") values[key] = value;
  }

  if (result.ok) {
    const nomor = nomorList(result.data);
    const pesan =
      (result.data as { pesan?: string } | null)?.pesan ??
      (nomor.length > 0
        ? `Nomor surat disisipkan: ${nomor.join(", ")}`
        : "Nomor surat berhasil disisipkan.");

    setFormFeedback(cookies, {
      target: "/surat-keluar/sisip",
      type: "success",
      message: pesan,
      result: summarizeSisipMeta(result.meta, nomor),
    });

    return redirect("/surat-keluar/sisip", 303);
  }

  setFormFeedback(cookies, {
    target: "/surat-keluar/sisip",
    type: "error",
    errors: errorFields(result.error),
    values,
    message:
      result.error?.message ??
      "Nomor surat gagal disisipkan. Silakan coba lagi.",
    code: result.error?.code ?? null,
  });

  return redirect("/surat-keluar/sisip", 303);
};
