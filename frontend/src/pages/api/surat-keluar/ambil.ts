import type { APIRoute } from "astro";
import { getSession } from "../../../lib/session";
import { apiFetch } from "../../../lib/api";
import { errorFields, submitAmbil } from "../../../lib/suratKeluar";
import { setFormFeedback } from "../../../lib/formFeedback";
import type { ApiUser } from "../../../lib/auth";

/**
 * Action route: ambil nomor surat baru.
 *
 * Form HTML mengirim POST ke sini; route meneruskan ke
 * `POST /api/v1/surat-keluar/ambil` dengan Bearer dari cookie httpOnly.
 * Browser tidak pernah melihat token.
 *
 * Hasil:
 *   201 → 303 ke daftar (admin: daftar-semua) tahun berjalan — sama dengan
 *         `Surat_keluar::ambil_simpan()` di CI3
 *   409 → 303 kembali ke /surat-keluar/ambil dengan kode
 *         `BERKAS_BELUM_LENGKAP`; halaman menampilkannya sebagai
 *         `.alert-warning` berisi pesan verbatim dari API
 *   422 → 303 kembali ke form, error per-field + nilai disimpan di cookie
 *   401 → 303 ke /login
 */
export const POST: APIRoute = async ({ cookies, request, redirect }) => {
  const token = getSession(cookies);
  if (!token) return redirect("/login", 303);

  let form: FormData;
  try {
    form = await request.formData();
  } catch {
    return redirect("/surat-keluar/ambil", 303);
  }

  const result = await submitAmbil(form, token);

  if (result.status === 401) return redirect("/login", 303);

  const values: Record<string, string> = {};
  for (const [key, value] of form.entries()) {
    if (typeof value === "string" && key !== "_method") values[key] = value;
  }

  if (result.ok) {
    // Tujuan redirect mengikuti peran, seperti controller CI3.
    const me = await apiFetch<ApiUser>("/auth/me", { token });
    const tahun = String(result.meta?.tahun ?? new Date().getFullYear());
    const target = me.data?.is_admin
      ? `/surat-keluar/daftar-semua?tahun=${tahun}`
      : `/surat-keluar/daftar?tahun=${tahun}`;

    return redirect(target, 303);
  }

  setFormFeedback(cookies, {
    target: "/surat-keluar/ambil",
    type: result.error?.code === "BERKAS_BELUM_LENGKAP" ? "warning" : "error",
    errors: errorFields(result.error),
    values,
    message:
      result.error?.message ?? "Nomor surat gagal diambil. Silakan coba lagi.",
    code: result.error?.code ?? null,
  });

  return redirect("/surat-keluar/ambil", 303);
};
