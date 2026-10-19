/**
 * Konfigurasi SSO yang bekerja di dev maupun produksi.
 *
 * Masalah yang diselesaikan:
 *
 * 1. `redirect_uri` harus PERSIS sama dengan yang terdaftar di
 *    `sso_db.sso_client.redirect_uris`. Di dev port-nya 4321, di produksi
 *    biasanya di belakang reverse proxy di port 80/443 dengan host lain.
 *    Karena itu origin tidak boleh ditebak dari `url.origin` saja — reverse
 *    proxy meneruskan `Host` internal, bukan host publik.
 *
 * 2. Cookie `secure` hanya boleh `true` bila halaman benar-benar https.
 *    Kalau dipaksa `true` di dev (http://localhost), browser membuang cookie
 *    dan alur login gagal tanpa pesan yang jelas.
 *
 * Urutan penentuan origin:
 *   SINOS_PUBLIC_ORIGIN  → eksplisit, dipakai di produksi di belakang proxy
 *   X-Forwarded-Proto/Host → bila proxy meneruskan header
 *   url.origin           → dev langsung (http://localhost:4321)
 */

/**
 * `process` tersedia di runtime Node (SSR) tetapi tidak dideklarasikan oleh
 * tsconfig `astro/tsconfigs/strict`. Deklarasi minimal di sini menghindari
 * penambahan `@types/node` hanya untuk satu lookup.
 */
declare const process: { env?: Record<string, string | undefined> } | undefined;

/**
 * Baca konfigurasi dari `process.env` lebih dulu, lalu `import.meta.env`.
 *
 * Astro meng-inline `import.meta.env` saat BUILD, sehingga nilai runtime
 * tidak akan terbaca bila hanya memakai `import.meta.env`. Dengan membaca
 * `process.env` lebih dulu, satu artefak build bisa dipakai untuk dev dan
 * produksi — cukup ganti environment saat `npm run start`.
 *
 * `process` hanya ada di server (SSR), dan seluruh helper ini hanya dipakai
 * di endpoint server, jadi aman.
 */
function env(key: string): string {
  const runtime = typeof process !== 'undefined' ? process.env?.[key] : undefined;
  if (runtime !== undefined && runtime !== '') return String(runtime).trim();

  const buildTime = (import.meta.env as Record<string, unknown>)[key];
  return buildTime === undefined || buildTime === null ? '' : String(buildTime).trim();
}

/** Origin publik aplikasi, tanpa garis miring di akhir. */
export function publicOrigin(url: URL, request: Request): string {
  const configured = env('SINOS_PUBLIC_ORIGIN');
  if (configured !== '') return configured.replace(/\/+$/, '');

  const fwdHost = request.headers.get('x-forwarded-host');
  if (fwdHost) {
    const proto = (request.headers.get('x-forwarded-proto') ?? url.protocol.replace(':', ''))
      .split(',')[0]
      .trim();
    return `${proto}://${fwdHost.split(',')[0].trim()}`;
  }

  return url.origin;
}

/** URL callback yang didaftarkan di gateway. */
export function redirectUri(url: URL, request: Request): string {
  return `${publicOrigin(url, request)}/api/auth/callback`;
}

/** Base URL gateway SSO, tanpa garis miring di akhir. */
export function gatewayBase(): string {
  return (env('SINOS_SSO_GATEWAY') || 'http://localhost/sso').replace(/\/+$/, '');
}

/**
 * Halaman SSO yang dibuka setelah pengguna keluar.
 *
 * Default: beranda gateway (launcher aplikasi). Dapat diarahkan ke halaman
 * lain — misalnya form login gateway — lewat `SINOS_SSO_HOME`.
 */
export function ssoHomeUrl(): string {
  const configured = env('SINOS_SSO_HOME');
  if (configured !== '') return configured;
  return `${gatewayBase()}/home`;
}

/**
 * URL logout gateway (GET).
 *
 * Gateway menghapus sesi login SSO lalu mengalihkan browser ke `redirect`.
 * Dipakai tombol "Keluar" agar sesi SSO benar-benar berakhir — bukan hanya
 * cookie Astro — dan pengguna kembali ke halaman SSO.
 */
export function ssoLogoutUrl(): string {
  return `${gatewayBase()}/logout?redirect=${encodeURIComponent(ssoHomeUrl())}`;
}

/** app_id klien di gateway. */
export function appId(): string {
  return env('SINOS_SSO_APP_ID') || 'sinos';
}

/**
 * Apakah cookie boleh ditandai `secure`.
 *
 * Hanya bila permintaan datang lewat https. `import.meta.env.PROD` saja tidak
 * cukup: build produksi bisa dijalankan di http://localhost untuk pengujian,
 * dan cookie `secure` akan dibuang browser.
 */
export function isSecureRequest(url: URL, request: Request): boolean {
  const proto = request.headers.get('x-forwarded-proto');
  if (proto) return proto.split(',')[0].trim() === 'https';
  return url.protocol === 'https:';
}

/** Opsi cookie yang konsisten untuk seluruh alur auth. */
export function cookieOptions(url: URL, request: Request, maxAge: number) {
  return {
    httpOnly: true,
    sameSite: 'lax' as const,
    secure: isSecureRequest(url, request),
    path: '/',
    maxAge,
  };
}
