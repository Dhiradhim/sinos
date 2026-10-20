/**
 * Middleware global — penjaga umpan balik form.
 *
 * Cookie `sinos_sm_feedback` ber-path "/" sehingga browser mengirimnya ke
 * SEMUA halaman. Halaman yang berhak menampilkannya memanggil
 * `takeFormFeedback(Astro.cookies, Astro.url.pathname)` dan menghapusnya.
 *
 * Masalah yang dicegah di sini: bila route action mengalihkan ke halaman yang
 * TIDAK memanggil fungsi itu (mis. hapus surat → /surat-masuk/daftar), cookie
 * tetap hidup sampai 120 detik dan akhirnya muncul di halaman berikutnya.
 *
 * Middleware menghapus cookie yang `target`-nya bukan halaman yang sedang
 * dibuka. Halaman tujuan tetap dapat membacanya karena target cocok.
 */
import { defineMiddleware } from "astro:middleware";
import {
  FEEDBACK_COOKIE,
  feedbackTarget,
  feedbackPath,
} from "./lib/formFeedback";

/**
 * Deklarasi lokal `process` — proyek ini sengaja tidak memasang `@types/node`
 * (pola yang sama dipakai src/lib/sso.ts:27).
 */
declare const process:
  | { env?: Record<string, string | undefined> }
  | undefined;

/**
 * Zona waktu proses server: WITA (UTC+8).
 *
 * `astro.config.mjs` menyetel TZ saat dev/build, tetapi server hasil build
 * (`node ./dist/server/entry.mjs`) tidak membaca berkas itu. Modul middleware
 * dievaluasi sekali saat server menyala, jadi baris ini memastikan `Date`
 * di sisi server — termasuk `new Date().getFullYear()` pada fallback tahun —
 * memakai UTC+8, bukan zona mesin.
 */
if (typeof process !== "undefined" && process.env) {
  process.env.TZ = process.env.SINOS_TZ ?? "Asia/Makassar";
}

export const onRequest = defineMiddleware((context, next) => {
  const cookie = context.cookies.get(FEEDBACK_COOKIE);

  if (cookie?.value) {
    const target = feedbackTarget(cookie.value);

    // Tanpa target (payload lama/rusak) atau target halaman lain: buang.
    // Jangan biarkan sisa pesan terbawa ke halaman yang tidak memintanya.
    if (!target || target !== feedbackPath(context.url.pathname)) {
      context.cookies.delete(FEEDBACK_COOKIE, { path: "/" });
    }
  }

  return next();
});
