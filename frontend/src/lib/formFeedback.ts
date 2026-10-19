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
 * PENTING — cookie ini TIDAK BOLEH muncul di halaman lain:
 *
 *   Cookie ber-path "/" sehingga browser mengirimnya ke SEMUA halaman. Bila
 *   halaman tujuan tidak membacanya, cookie tetap hidup sampai `maxAge` habis
 *   lalu muncul di halaman berikutnya yang kebetulan membacanya. Itulah sebab
 *   notifikasi "berhasil dihapus" bisa tampil di halaman Input Surat Masuk.
 *
 *   Karena itu setiap umpan balik menyimpan `target` (path halaman yang
 *   berhak menampilkannya). `takeFormFeedback()` selalu menghapus cookie
 *   (sekali pakai) dan hanya mengembalikan isi bila path halaman saat ini
 *   sama dengan `target`. Middleware juga menghapus cookie yang targetnya
 *   bukan halaman yang sedang dibuka, sehingga tidak ada sisa yang terbawa.
 *
 * Cookie:
 *   - httpOnly, SameSite=Lax, umur 120 detik, path '/';
 *   - isi = base64url(JSON.stringify({ target, type, errors, values, message })).
 */
import type { AstroCookies } from "astro";

export const FEEDBACK_COOKIE = "sinos_sm_feedback";

/** Nada tampilan pesan. */
export type FormFeedbackType = "success" | "error" | "warning" | "info";

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
  /**
   * Path halaman yang berhak menampilkan pesan ini (tanpa query).
   * Wajib diisi oleh route action. Tanpa ini, pesan dapat bocor ke halaman
   * lain karena cookie ber-path "/".
   */
  target?: string | null;
  /** Nada pesan: success | error | warning | info. */
  type?: FormFeedbackType | null;
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
 * Buang query/hash dari sebuah path agar perbandingan target stabil.
 * `/surat-masuk/daftar?tahun=2026` → `/surat-masuk/daftar`.
 */
export function feedbackPath(path: string): string {
  const cut = path.search(/[?#]/);
  const value = cut === -1 ? path : path.slice(0, cut);
  if (value.length > 1 && value.endsWith("/")) return value.slice(0, -1);
  return value || "/";
}

/** Target halaman dari nilai cookie mentah, atau null bila tidak terbaca. */
export function feedbackTarget(encoded: string): string | null {
  try {
    const parsed = JSON.parse(decodeBase64Url(encoded)) as FormFeedback;
    const target = typeof parsed?.target === "string" ? parsed.target.trim() : "";
    return target === "" ? null : feedbackPath(target);
  } catch {
    return null;
  }
}

/**
 * Tulis umpan balik. Bila terlalu besar, nilai yang diketik dikorbankan lebih
 * dulu, lalu error per-field; `target`, `type`, pesan, kode, dan ringkasan
 * hasil dipertahankan karena itulah yang dibaca pengguna setelah redirect.
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
      target: feedback.target,
      type: feedback.type,
      errors: feedback.errors,
      message: feedback.message,
      code: feedback.code,
      result: feedback.result,
    });
  }
  if (encoded.length > MAX_BYTES) {
    encoded = write({
      target: feedback.target,
      type: feedback.type,
      message: feedback.message,
      code: feedback.code,
      result: feedback.result,
    });
  }
  if (encoded.length > MAX_BYTES) {
    encoded = write({
      target: feedback.target,
      type: feedback.type,
      message: feedback.message,
      code: feedback.code,
    });
  }

  cookies.set(FEEDBACK_COOKIE, encoded, {
    httpOnly: true,
    sameSite: "lax",
    secure: import.meta.env.PROD,
    path: "/",
    maxAge: 120,
  });
}

/**
 * Baca lalu hapus umpan balik (sekali pakai).
 *
 * Cookie SELALU dihapus, bahkan ketika isinya tidak ditampilkan. Bila
 * `pathname` diberikan dan `target` di cookie menunjuk halaman lain, fungsi
 * mengembalikan `null` — pesan tidak boleh muncul di halaman yang salah.
 *
 * @param cookies  Cookie jar Astro.
 * @param pathname Path halaman saat ini (biasanya `Astro.url.pathname`).
 */
export function takeFormFeedback(
  cookies: AstroCookies,
  pathname?: string,
): FormFeedback | null {
  const cookie = cookies.get(FEEDBACK_COOKIE);
  if (!cookie || !cookie.value) return null;

  // Sekali pakai: hapus sebelum parsing agar cookie rusak pun tidak tersisa.
  cookies.delete(FEEDBACK_COOKIE, { path: "/" });

  let parsed: FormFeedback;
  try {
    parsed = JSON.parse(decodeBase64Url(cookie.value)) as FormFeedback;
  } catch {
    return null;
  }
  if (!parsed || typeof parsed !== "object") return null;

  // Umpan balik milik halaman lain: jangan tampilkan.
  if (pathname) {
    const target =
      typeof parsed.target === "string" ? parsed.target.trim() : "";
    if (target !== "" && feedbackPath(target) !== feedbackPath(pathname)) {
      return null;
    }
  }

  return parsed;
}
