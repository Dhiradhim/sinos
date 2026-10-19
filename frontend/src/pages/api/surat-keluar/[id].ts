import type { APIRoute } from "astro";
import { getSession } from "../../../lib/session";
import { apiFetch } from "../../../lib/api";
import { errorFields, submitUpdate } from "../../../lib/suratKeluar";
import { setFormFeedback } from "../../../lib/formFeedback";
import type { ApiUser } from "../../../lib/auth";

/**
 * Action route: simpan perubahan nomor surat.
 *
 * `submitUpdate()` mengirim POST + `_method=PUT`; API memperlakukannya sebagai
 * PUT (`Surat_keluar::method_efektif()`). `no_urut` dan `huruf` tidak pernah
 * dikirim — server mempertahankannya.
 *
 * Hasil:
 *   200 → 303 ke daftar (admin: daftar-semua) tahun tanggal surat, sama
 *         dengan `Surat_keluar::update()`
 *   422 → 303 kembali ke /surat-keluar/edit/{id} dengan pesan + nilai
 *   403 → 303 kembali ke form dengan pesan API
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
    return redirect(`/surat-keluar/edit/${id}`, 303);
  }

  const result = await submitUpdate(form, token, id);

  if (result.status === 401) return redirect("/login", 303);

  if (result.ok && result.data) {
    const me = await apiFetch<ApiUser>("/auth/me", { token });
    const tahun =
      String(result.data.tanggal?.iso ?? "").slice(0, 4) ||
      String(new Date().getFullYear());
    const target = me.data?.is_admin
      ? `/surat-keluar/daftar-semua?tahun=${tahun}`
      : `/surat-keluar/daftar?tahun=${tahun}`;

    return redirect(target, 303);
  }

  const values: Record<string, string> = {};
  for (const [key, value] of form.entries()) {
    if (typeof value === "string" && key !== "_method") values[key] = value;
  }

  setFormFeedback(cookies, {
    target: `/surat-keluar/edit/${id}`,
    type: "error",
    errors: errorFields(result.error),
    values,
    message:
      result.error?.message ??
      "Perubahan nomor surat gagal disimpan. Silakan coba lagi.",
    code: result.error?.code ?? null,
  });

  return redirect(`/surat-keluar/edit/${id}`, 303);
};
