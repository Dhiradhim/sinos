/**
 * Pemetaan tone netral dari API ke kelas badge.
 *
 * Ini SATU-SATUNYA tempat pemetaan tone → kelas berada. Komponen tidak boleh
 * menulis nama tone sebagai kelas Tailwind sendiri, agar palet status arah B
 * tetap satu sumber.
 *
 * Tone dari API: brand | sky | amber | danger | ok | muted | neutral | warning | info
 */
const TONE_CLASS: Record<string, string> = {
  brand: 'badge-default',
  sky: 'badge-info',
  info: 'badge-info',
  amber: 'badge-warning',
  warning: 'badge-warning',
  danger: 'badge-danger',
  ok: 'badge-success',
  success: 'badge-success',
  muted: 'badge-muted',
  neutral: 'badge-outline',
};

export function toneClass(tone?: string | null): string {
  if (!tone) return 'badge-muted';
  return TONE_CLASS[tone.toLowerCase()] ?? 'badge-muted';
}

/**
 * Tone → warna tinta untuk ikon (bukan badge).
 *
 * Arah B: angka statistik tidak memakai pil. Ikon kecil di sebelah angka
 * diwarnai sesuai tone agar empat kelompok statistik tetap dapat dibedakan
 * tanpa kartu berwarna.
 */
const TONE_INK: Record<string, string> = {
  brand: 'var(--color-brand-900)',
  sky: 'var(--color-sky-700)',
  info: 'var(--color-sky-700)',
  amber: 'var(--color-warn)',
  warning: 'var(--color-warn)',
  danger: 'var(--color-danger)',
  ok: 'var(--color-ok)',
  success: 'var(--color-ok)',
  muted: 'var(--color-ink-subtle)',
  neutral: 'var(--color-ink-muted)',
};

export function toneInk(tone?: string | null): string {
  if (!tone) return 'var(--color-ink-subtle)';
  return TONE_INK[tone.toLowerCase()] ?? 'var(--color-ink-subtle)';
}

/**
 * URL berkas dari `file.path` (mis. 'file/2026_1458_file.pdf').
 *
 * `api_berkas_helper.php` menyatakan `path` berisi nilai kolom APA ADANYA dan
 * Astro yang membentuk URL. Direktori `file/` diblokir `.htaccess`, sehingga
 * tautan wajib melewati endpoint bertanda tangan:
 *   GET /api/v1/berkas/{token}
 * Endpoint itu menerima token berkas ATAU Bearer token; proxy Astro mengirim
 * Bearer dari cookie httpOnly, jadi tidak perlu menerbitkan token di klien.
 * Bila backend kelak mengirim URL bertanda tangan utuh, nilai itu dipakai apa
 * adanya.
 */
export function berkasUrl(path?: string | null): string {
  const value = (path ?? '').trim();
  if (value === '') return '';
  if (/^https?:\/\//i.test(value) || value.startsWith('/')) return value;
  return `/api/berkas/${encodeURIComponent(value)}`;
}

/** Status berkas: none | pending | ready. */
export function fileLabel(state?: string | null): string {
  switch (state) {
    case 'ready':
      return 'Lihat';
    case 'pending':
      return 'Belum ada';
    case 'none':
    default:
      return '—';
  }
}

/**
 * Zona waktu aplikasi: WITA (UTC+8).
 *
 * SEMUA tanggal/waktu yang ditampilkan dirender pada zona ini, bukan zona
 * server atau browser, supaya staf di lapangan melihat jam yang sama dengan
 * yang tercatat di database. Nilai dari API biasanya "naif" (tanpa offset,
 * mis. `2026-10-20 00:02:00`), jadi nilai naif ditafsirkan SEBAGAI waktu
 * UTC+8 — bukan waktu lokal server.
 */
export const TIMEZONE = 'Asia/Makassar';

/** Offset tetap UTC+8 (WITA tidak memakai DST). */
const TIMEZONE_OFFSET = '+08:00';

/** Naif: YYYY-MM-DD, opsional dengan jam tanpa offset/Z. */
const NAIVE = /^\d{4}-\d{2}-\d{2}(?:[T ]\d{2}:\d{2}(?::\d{2})?(?:\.\d+)?)?$/;

function toDate(iso?: string | null): Date | null {
  if (!iso) return null;
  const value = String(iso).trim();
  if (value === '') return null;

  // Nilai naif: tempelkan offset UTC+8 agar tidak bergeser mengikuti zona
  // server. Ini juga menangani tanggal tanpa waktu (00:00 WITA).
  if (NAIVE.test(value)) {
    const date = new Date(`${value.replace(' ', 'T')}${TIMEZONE_OFFSET}`);
    return Number.isNaN(date.getTime()) ? null : date;
  }

  // Nilai ber-offset (Z / ±HH:MM) dipakai apa adanya.
  const date = new Date(value);
  return Number.isNaN(date.getTime()) ? null : date;
}

/** "17 Oktober 2026" */
export function formatDate(iso?: string | null): string {
  const date = toDate(iso);
  if (!date) return '—';
  return new Intl.DateTimeFormat('id-ID', {
    timeZone: TIMEZONE,
    day: 'numeric',
    month: 'long',
    year: 'numeric',
  }).format(date);
}

/** "17 Oktober 2026, 14.30" */
export function formatDateTime(iso?: string | null): string {
  const date = toDate(iso);
  if (!date) return '—';
  return new Intl.DateTimeFormat('id-ID', {
    timeZone: TIMEZONE,
    day: 'numeric',
    month: 'long',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  }).format(date);
}

/** Komponen tanggal (YYYY-MM-DD) menurut UTC+8. */
function zonedDateParts(date: Date): {
  year: number;
  month: string;
  day: string;
} {
  const parts = new Intl.DateTimeFormat('en-CA', {
    timeZone: TIMEZONE,
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
  }).formatToParts(date);
  const pick = (type: string): string =>
    parts.find((part) => part.type === type)?.value ?? '';

  return {
    year: Number(pick('year')),
    month: pick('month'),
    day: pick('day'),
  };
}

/**
 * Tahun berjalan menurut UTC+8.
 *
 * Dipakai sebagai fallback `tahun` di halaman dan sebagai pengganti
 * `new Date().getFullYear()` (yang memakai zona server).
 */
export function currentYear(date: Date = new Date()): number {
  return zonedDateParts(date).year;
}

/** Tanggal hari ini (YYYY-MM-DD) menurut UTC+8 — untuk nilai awal input. */
export function todayISO(date: Date = new Date()): string {
  const { year, month, day } = zonedDateParts(date);
  return `${year}-${month}-${day}`;
}

/** "1.536" — angka statistik memakai pemisah ribuan Indonesia. */
export function formatNumber(value?: number | string | null): string {
  const num = Number(value ?? 0);
  if (!Number.isFinite(num)) return '0';
  return new Intl.NumberFormat('id-ID').format(num);
}
