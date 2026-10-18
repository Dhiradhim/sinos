import { defineConfig } from 'astro/config';
import node from '@astrojs/node';
import tailwindcss from '@tailwindcss/vite';

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
