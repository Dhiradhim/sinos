import type { APIRoute } from "astro";
import { getSession } from "../../../../lib/session";
import { submitHapus } from "../../../../lib/suratKeluar";
import { setFormFeedback } from "../../../../lib/formFeedback";

/**
 * Action route: hapus nomor surat keluar (ADMIN saja).
 *
 * `POST` dipakai karena form HTML tidak dapat mengirim DELETE; helper
 * `submitHapus()` mengirim `POST /api/v1/surat-keluar/{id}` dengan body
 * `_method=DELETE`, yang dikenali `Surat_keluar::method_efektif()`.
 *
 * Gerbang admin ditegakkan backend (`Surat_keluar::destroy()`); route ini
 * hanya meneruskan pesan galatnya sehingga klien tidak dapat melewatinya.
 *
 * Tujuan redirect divalidasi agar hanya path `/surat-keluar…` yang diterima
 * (mencegah open redirect lewat `?kembali=`).
 */
function safeReturn(raw: string | null, fallback: string): string {
  const value = (raw ?? "").trim();
  if (value.startsWith("/surat-keluar")) return value;
  return fallback;
}

export const POST: APIRoute = async ({ cookies, params, request, redirect }) => {
  const token = getSession(cookies);
  if (!token) return redirect("/login", 303);

  const id = Number(params.id);
  if (!Number.isInteger(id) || id <= 0) {
    return redirect("/surat-keluar/daftar-semua", 303);
  }

  const url = new URL(request.url);
  const kembali = safeReturn(
    url.searchParams.get("kembali"),
    "/surat-keluar/daftar-semua",
  );

  const result = await submitHapus(token, id);

  if (result.status === 401) return redirect("/login", 303);

  const nomor = result.data?.no ? ` ${result.data.no}` : "";

  setFormFeedback(cookies, {
    target: kembali,
    type: result.ok ? "success" : "error",
    message: result.ok
      ? `Nomor surat${nomor} berhasil dihapus.`
      : (result.error?.message ?? "Nomor surat gagal dihapus."),
    code: result.error?.code ?? null,
  });

  return redirect(kembali, 303);
};
