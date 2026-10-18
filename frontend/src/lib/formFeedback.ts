/**
 * Umpan balik form lintas-redirect (Post/Redirect/Get).
 *
 * Form surat masuk dikirim sebagai POST biasa (bukan fetch), sehingga route
 * action tidak dapat "merender ulang" halaman form secara langsung. Bila API
 * menolak dengan 422, route menyimpan pesan + nilai yang sempat diketik ke
 * cookie sekali pakai, lalu mengalihkan (303) kembali ke halaman form. Halaman
 * membaca cookie itu, menampilkannya, dan menghapusnya — sehingga pengguna
 * tidak perlu mengetik ulang.
 *
 * Cookie:
 *   - httpOnly, SameSite=Lax, umur 120 detik, path '/';
 *   - isi = base64url(JSON.stringify({ errors, values, message })).
 */
import type { AstroCookies } from "astro";

export const FEEDBACK_COOKIE = "sinos_sm_feedback";

export type FormFeedback = {
  errors?: Record<string, string> | null;
  values?: Record<string, string> | null;
  message?: string | null;
  /**
   * Kode mesin dari envelope error API (mis. `BERKAS_BELUM_LENGKAP`).
   * Dipakai halaman untuk memilih jenis peringatan (409 vs 422) tanpa
   * menebak dari teks pesan.
   */
  code?: string | null;
  /**
   * Ringkasan hasil sukses yang perlu bertahan melewati redirect 303
   * (mis. audit `meta` dari POST /surat-keluar/sisip). Nilai sudah
   * berbentuk string agar aman di cookie.
   */
  result?: Record<string, string> | null;
};

/** Batas ukuran cookie agar tidak melewati batas browser (~4 KB). */
const MAX_BYTES = 3000;

function encodeBase64Url(text: string): string {
  let binary = "";
  for (const byte of new TextEncoder().encode(text)) {
    binary += String.fromCharCode(byte);
  }
  return btoa(binary).replace(/\+/g, "-").replace(/\//g, "_").replace(/=+$/, "");
}

function decodeBase64Url(value: string): string {
  const padded = value.replace(/-/g, "+").replace(/_/g, "/");
  const binary = atob(padded + "=".repeat((4 - (padded.length % 4)) % 4));
  const bytes = Uint8Array.from(binary, (ch) => ch.charCodeAt(0));
  return new TextDecoder().decode(bytes);
}

/**
 * Tulis umpan balik. Bila terlalu besar, nilai yang diketik dikorbankan lebih
 * dulu, lalu error per-field; pesan, kode, dan ringkasan hasil dipertahankan
 * karena itulah yang dibaca pengguna setelah redirect.
 */
export function setFormFeedback(
  cookies: AstroCookies,
  feedback: FormFeedback,
): void {
  const write = (payload: FormFeedback) =>
    encodeBase64Url(JSON.stringify(payload));

  let encoded = write(feedback);

  if (encoded.length > MAX_BYTES) {
    encoded = write({
      errors: feedback.errors,
      message: feedback.message,
      code: feedback.code,
      result: feedback.result,
    });
  }
  if (encoded.length > MAX_BYTES) {
    encoded = write({
      message: feedback.message,
      code: feedback.code,
      result: feedback.result,
    });
  }
  if (encoded.length > MAX_BYTES) {
    encoded = write({ message: feedback.message, code: feedback.code });
  }

  cookies.set(FEEDBACK_COOKIE, encoded, {
    httpOnly: true,
    sameSite: "lax",
    secure: import.meta.env.PROD,
    path: "/",
    maxAge: 120,
  });
}

/** Baca lalu hapus umpan balik (sekali pakai). */
export function takeFormFeedback(cookies: AstroCookies): FormFeedback | null {
  const cookie = cookies.get(FEEDBACK_COOKIE);
  if (!cookie || !cookie.value) return null;

  cookies.delete(FEEDBACK_COOKIE, { path: "/" });

  try {
    const parsed = JSON.parse(decodeBase64Url(cookie.value)) as FormFeedback;
    return parsed && typeof parsed === "object" ? parsed : null;
  } catch {
    return null;
  }
}
