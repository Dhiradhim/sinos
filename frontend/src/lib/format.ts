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

const DATE_ONLY = /^\d{4}-\d{2}-\d{2}$/;

function toDate(iso?: string | null): Date | null {
  if (!iso) return null;
  const value = String(iso).trim();
  if (value === '') return null;

  // Tanggal tanpa waktu: hindari pergeseran zona waktu saat diparse sebagai UTC.
  const normalized = DATE_ONLY.test(value) ? `${value}T00:00:00` : value.replace(' ', 'T');
  const date = new Date(normalized);
  return Number.isNaN(date.getTime()) ? null : date;
}

/** "17 Oktober 2026" */
export function formatDate(iso?: string | null): string {
  const date = toDate(iso);
  if (!date) return '—';
  return new Intl.DateTimeFormat('id-ID', {
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
    day: 'numeric',
    month: 'long',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  }).format(date);
}

/** "1.536" — angka statistik memakai pemisah ribuan Indonesia. */
export function formatNumber(value?: number | string | null): string {
  const num = Number(value ?? 0);
  if (!Number.isFinite(num)) return '0';
  return new Intl.NumberFormat('id-ID').format(num);
}
