import type { APIRoute } from "astro";
import { getSession } from "../../../../lib/session";
import { currentYear } from "../../../../lib/format";
import { errorFields, submitBerkas } from "../../../../lib/suratKeluar";
import { setFormFeedback } from "../../../../lib/formFeedback";

/**
 * Action route: unggah berkas PDF untuk satu nomor surat.
 *
 * Form multipart diteruskan apa adanya ke
 * `POST /api/v1/surat-keluar/{id}/berkas`; hanya field `file` yang
 * disertakan (nama field dibaca `api/Surat_keluar::berkas()` dari
 * `$_FILES['file']`).
 *
 * Hasil:
 *   200 → 303 ke /surat-keluar/daftar?tahun={tahun tanggal} dengan pesan
 *         "Berkas berhasil diunggah." (flash CI3) — bukan ke /dashboard,
 *         karena arah B menjaga pengguna di modul yang sama
 *   422 → 303 kembali ke /surat-keluar/upload/{id} dengan pesan
 *   403 → 303 kembali ke halaman upload dengan pesan API
 *   401 → 303 ke /login
 */
export const POST: APIRoute = async ({ cookies, request, params, redirect }) => {
  const token = getSession(cookies);
  const id = String(params.id ?? "").trim();
  if (!token) return redirect("/login", 303);
  if (id === "") return redirect("/surat-keluar/daftar", 303);

  let form: FormData;
  try {
    form = await request.formData();
  } catch {
    return redirect(`/surat-keluar/upload/${id}`, 303);
  }

  const result = await submitBerkas(form, token, id);

  if (result.status === 401) return redirect("/login", 303);

  if (result.ok && result.data) {
    const tahun =
      String(result.data.tanggal?.iso ?? "").slice(0, 4) ||
      String(currentYear());

    setFormFeedback(cookies, {
      target: "/surat-keluar/daftar",
      type: "success",
      message: "Berkas berhasil diunggah.",
    });

    return redirect(`/surat-keluar/daftar?tahun=${tahun}`, 303);
  }

  setFormFeedback(cookies, {
    target: `/surat-keluar/upload/${id}`,
    type: "error",
    errors: errorFields(result.error),
    message: result.error?.message ?? "Berkas gagal diunggah. Silakan coba lagi.",
    code: result.error?.code ?? null,
  });

  return redirect(`/surat-keluar/upload/${id}`, 303);
};
