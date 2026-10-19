import type { APIRoute } from 'astro';
import { getSession } from '../../../lib/session';

/**
 * Proxy berkas ber-auth.
 *
 * Kenapa route ini ada
 * `GET /api/v1/dashboard` mengirim `file.path` sebagai nilai kolom APA ADANYA
 * (mis. `file/2026_1458_file.pdf`) — bukan URL bertanda tangan. Direktori
 * `file/` diblokir `.htaccess` (sinos/.htaccess:29), dan
 * `api/Berkas::show()` mewajibkan segmen path berupa token berkas HMAC yang
 * valid. Jadi tautan mentah ke `file/...` akan 403, dan tautan ke
 * `/api/v1/berkas/file/...` akan 401 karena segmen itu bukan token.
 *
 * Route ini menutup celah tersebut di sisi Astro (BFF), tanpa mengubah backend:
 *   1. wajib ada cookie sesi `sinos_sess`;
 *   2. menyusun token berkas dengan format yang sama seperti
 *      `api_berkas_sign()` (sinos/application/helpers/api_token_helper.php:322):
 *      base64url({typ:'berkas',p,iat,exp}) + '.' + base64url(hmac_sha256);
 *   3. mengambil berkas dari CI3 dan meneruskannya ke browser.
 *
 * Implementasi memakai Web Crypto (tersedia sebagai global di Node 18+), bukan
 * `node:crypto`, agar tidak menambah dependensi `@types/node` ke proyek.
 *
 * Secret diambil dari `SINOS_API_SECRET`. Bila kosong, dipakai turunan
 * fallback yang sama dengan `api_token_secret()`:
 *   sha256('sinos-api|' + base_url + '|' + ENVIRONMENT)
 * Turunan itu TIDAK rahasia; set `SINOS_API_SECRET` (nilai yang sama dengan
 * `API_SECRET` di sinos/.env) sebelum produksi.
 */
const API_BASE = (import.meta.env.SINOS_API_BASE ?? 'http://localhost/sinos/api/v1').replace(/\/+$/, '');
const API_ROOT = API_BASE.replace(/\/api\/v1$/, '');
const FILE_TTL_SECONDS = 300;

const encoder = new TextEncoder();

function b64url(bytes: Uint8Array): string {
  let binary = '';
  for (const byte of bytes) binary += String.fromCharCode(byte);
  return btoa(binary).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
}

function b64urlText(text: string): string {
  return b64url(encoder.encode(text));
}

async function hmacSha256(keyBytes: Uint8Array, message: string): Promise<Uint8Array> {
  const key = await crypto.subtle.importKey(
    'raw',
    keyBytes as unknown as BufferSource,
    { name: 'HMAC', hash: 'SHA-256' },
    false,
    ['sign'],
  );
  const signature = await crypto.subtle.sign('HMAC', key, encoder.encode(message));
  return new Uint8Array(signature);
}

async function sha256Hex(text: string): Promise<string> {
  const digest = await crypto.subtle.digest('SHA-256', encoder.encode(text));
  return Array.from(new Uint8Array(digest))
    .map((byte) => byte.toString(16).padStart(2, '0'))
    .join('');
}

/** Secret HMAC, dengan fallback turunan yang sama seperti api_token_secret(). */
async function apiSecret(): Promise<string> {
  const explicit = (import.meta.env.SINOS_API_SECRET ?? '').trim();
  if (explicit !== '' && !explicit.startsWith('GANTI_')) return explicit;

  const baseUrl = `${API_ROOT}/`;
  const environment = import.meta.env.PROD ? 'production' : 'development';
  return sha256Hex(`sinos-api|${baseUrl}|${environment}`);
}

/**
 * Normalisasi path relatif; menolak path absolut, backslash, dan '..'.
 *
 * PENTING — path dari `params.path` MASIH ter-encode.
 *
 * `berkasUrl()` membentuk URL dengan `encodeURIComponent(path)` sehingga
 * seluruh path menjadi SATU segmen:
 *   'file/sm/x.pdf' → 'file%2Fsm%2Fx.pdf'
 *
 * Astro meneruskan segmen dinamis (`[...path]`) APA ADANYA, tanpa men-decode.
 * Nilai yang tiba di sini karena itu masih `file%2Fsm%2Fx.pdf`. Bila nilai
 * itu ditandatangani tanpa di-decode, backend menerima `p = file%2Fsm%2Fx.pdf`
 * yang tidak diawali `file/` dan menolaknya dengan `BERKAS_DILARANG` —
 * persis gejala "Akses berkas di luar direktori yang diizinkan." pada tautan
 * berkas yang sah.
 *
 * Karena itu decode dilakukan TEPAT SEKALI di sini, lalu divalidasi. Decode
 * ganda tidak terjadi: hasil decode tidak pernah di-decode lagi.
 */
function normalizePath(raw: string): string | null {
  let decoded: string;
  try {
    decoded = decodeURIComponent(raw);
  } catch {
    // Urutan escape rusak (mis. '%zz'): tolak, jangan diteruskan.
    return null;
  }

  const path = decoded.replace(/^\/+/, '').trim();
  if (path === '' || path.includes('\\') || path.includes('\0')) return null;
  if (/^[A-Za-z]:/.test(path) || path.includes('://')) return null;
  if (path.split('/').some((segment) => segment === '..' || segment === '.')) return null;
  return path;
}

async function signBerkas(path: string): Promise<string> {
  const now = Math.floor(Date.now() / 1000);
  const payload = b64urlText(
    JSON.stringify({ typ: 'berkas', p: path, iat: now, exp: now + FILE_TTL_SECONDS }),
  );
  const signature = b64url(await hmacSha256(encoder.encode(await apiSecret()), payload));
  return `${payload}.${signature}`;
}

function textResponse(message: string, status: number): Response {
  return new Response(message, {
    status,
    headers: { 'Content-Type': 'text/plain; charset=utf-8', 'Cache-Control': 'no-store' },
  });
}

export const GET: APIRoute = async ({ cookies, params, request }) => {
  const token = getSession(cookies);
  if (!token) return textResponse('Sesi Anda sudah berakhir. Silakan masuk kembali.', 401);

  const path = normalizePath(params.path ?? '');
  if (path === null) return textResponse('Akses berkas di luar direktori yang diizinkan.', 403);

  let upstream: Response;
  try {
    upstream = await fetch(`${API_BASE}/berkas/${await signBerkas(path)}`, {
      headers: { Accept: 'application/pdf', Authorization: `Bearer ${token}` },
      signal: request.signal,
    });
  } catch {
    return textResponse('Tidak dapat menghubungi server SINOS.', 502);
  }

  /**
   * Status upstream diteruskan apa adanya, bukan dilipat menjadi 502:
   *   401 — token berkas ditolak (tanda tangan salah / kedaluwarsa);
   *   403 — path di luar `file/`;
   *   404 — berkas tidak ada di disk.
   * Sebelumnya semua kegagalan menjadi 502, sehingga pemanggil tidak dapat
   * membedakan "tidak ditemukan" dari "server rusak".
   */
  if (!upstream.ok || !upstream.body) {
    if (upstream.status === 401) {
      return textResponse('Akses berkas ditolak: sesi atau token berkas tidak valid.', 401);
    }
    if (upstream.status === 403) {
      return textResponse('Akses berkas di luar direktori yang diizinkan.', 403);
    }
    if (upstream.status === 404) {
      return textResponse('Berkas tidak ditemukan.', 404);
    }
    return textResponse('Berkas tidak dapat dibuka. Coba muat ulang halaman.', 502);
  }

  return new Response(upstream.body, {
    status: 200,
    headers: {
      'Content-Type': upstream.headers.get('Content-Type') ?? 'application/pdf',
      'Content-Disposition': upstream.headers.get('Content-Disposition') ?? 'inline',
      // Berkas privat: jangan pernah masuk cache bersama.
      'Cache-Control': 'private, max-age=60',
    },
  });
};
