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
 * Meneruskan ke `POST /api/v1/surat-keluar/sisip`. Pada 201, ringkasan hasil
 * disimpan di cookie umpan balik sekali pakai.
 *
 * Hasil:
 *   201 → 303 ke /surat-keluar/daftar-semua (permintaan pengguna), sehingga
 *         popup "Nomor Surat Berhasil Dibuat" langsung tampil di daftar —
 *         sama seperti alur "Ambil Nomor". Sebelumnya halaman tetap di form
 *         sisip, sehingga popup tidak pernah muncul.
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

    /**
     * `result` dipakai dua hal sekaligus: ringkasan audit sisip (nomor urut
     * sebelumnya, nomor/huruf terpakai) DAN rincian nomor untuk popup
     * "Nomor Surat Berhasil Dibuat" (nomor, tanggal, perihal, tujuan).
     *
     * `nomor` (kunci yang dibaca daftar-semua untuk memicu popup) diisi
     * nomor yang baru dibuat — bukan ringkasan audit — agar popup langsung
     * tampil setelah redirect.
     */
    const tujuan = `/surat-keluar/daftar-semua?tahun=${String(
      result.meta?.tahun ?? values.tanggal?.slice(0, 4) ?? "",
    )}`;

    setFormFeedback(cookies, {
      target: "/surat-keluar/daftar-semua",
      type: "success",
      message: pesan,
      result: {
        ...summarizeSisipMeta(result.meta, nomor),
        nomor: nomor.join(", "),
        nomor_popup: nomor.join(", "),
        tanggal: values.tanggal ?? "",
        hal: values.hal ?? "",
        tujuan: values.tujuan ?? "",
        mode: "sisip",
      },
    });

    return redirect(tujuan, 303);
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
