import { defineConfig } from 'astro/config';
import node from '@astrojs/node';
import tailwindcss from '@tailwindcss/vite';

/**
 * Zona waktu aplikasi: WITA (UTC+8).
 *
 * Disetel SEBELUM proses Node menjalankan kode apa pun, sehingga
 * `new Date().getFullYear()`, `getDate()`, dan sejenisnya pada server
 * mengikuti UTC+8 — bukan zona mesin (yang di produksi bisa UTC).
 * Nilai dapat ditimpa lewat env `SINOS_TZ` bila deployment memerlukan
 * zona lain; nilai default disamakan dengan `TIMEZONE` di src/lib/format.ts.
 */
process.env.TZ = process.env.SINOS_TZ ?? 'Asia/Makassar';

export default defineConfig({
  output: 'server',
  adapter: node({ mode: 'standalone' }),
  vite: { plugins: [tailwindcss()] },
  server: { port: 4321 },

  // Toolbar dev Astro (tombol melayang di bawah) dimatikan.
  // Alasan: aplikasi ini dipakai staf sebagai sistem kerja, bukan preview
  // desain — toolbar menutupi konten dan tidak ada gunanya di sini.
  devToolbar: { enabled: false },
});
