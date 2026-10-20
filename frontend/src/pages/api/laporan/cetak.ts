import type { APIRoute } from 'astro';
import { API_BASE } from '../../../lib/api';
import { getSession } from '../../../lib/session';
import { todayISO } from '../../../lib/format';

/**
 * Proxy BFF: cetak laporan PDF.
 *
 *   POST /api/laporan/cetak  →  POST /api/v1/laporan/cetak  (application/pdf)
 *
 * Kenapa route ini ada
 * API mewajibkan Bearer token, sedangkan `<form method="post" target="_blank">`
 * tidak dapat mengirim header. Form di `laporan/index.astro` karena itu
 * menunjuk ke route ini; token dibaca dari cookie httpOnly dan diteruskan
 * server-side, sehingga browser tetap hanya menerima biner PDF.
 *
 * Yang diteruskan balik:
 *   - status upstream (403 untuk non-admin, 422/500 bila gagal);
 *   - `Content-Type` (application/pdf);
 *   - `Content-Disposition` apa adanya, sehingga nama berkas tetap
 *     `laporan_{tipelaporan}_{tahun}.pdf` seperti CI3;
 *   - `Content-Length` bila upstream mengirimnya.
 *
 * Body diparse dari FormData (form HTML biasa) lalu dikirim sebagai JSON,
 * karena `MY_Api_Controller::input_json()` membaca raw stream lebih dulu.
 * Nama field persis kontrak: tipelaporan, tgl_cetak, bulan, tahun, kode.
 */

function text(message: string, status: number): Response {
  return new Response(message, {
    status,
    headers: {
      'Content-Type': 'text/plain; charset=utf-8',
      'Cache-Control': 'no-store',
    },
  });
}

export const POST: APIRoute = async ({ cookies, request }) => {
  const token = getSession(cookies);
  if (!token) return text('Sesi Anda sudah berakhir. Silakan masuk kembali.', 401);

  let form: FormData;
  try {
    form = await request.formData();
  } catch {
    return text('Permintaan cetak laporan tidak valid.', 400);
  }

  const read = (name: string, fallback: string): string => {
    const value = form.get(name);
    return typeof value === 'string' && value.trim() !== ''
      ? value.trim()
      : fallback;
  };

  // Default tanggal cetak memakai zona aplikasi (UTC+8), bukan zona mesin.
  const [tahunNow, bulanNow, hariNow] = todayISO().split('-');
  const tahunDefault = tahunNow;
  const tglDefault = `${hariNow}-${bulanNow}-${tahunNow}`;

  const payload = {
    tipelaporan: read('tipelaporan', 'surkel'),
    tgl_cetak: read('tgl_cetak', tglDefault),
    bulan: read('bulan', '00'),
    tahun: read('tahun', tahunDefault),
    kode: read('kode', 'all'),
  };

  let upstream: Response;
  try {
    upstream = await fetch(`${API_BASE}/laporan/cetak`, {
      method: 'POST',
      headers: {
        Accept: 'application/pdf',
        'Content-Type': 'application/json',
        Authorization: `Bearer ${token}`,
      },
      body: JSON.stringify(payload),
      signal: request.signal,
    });
  } catch {
    return text('Tidak dapat menghubungi server SINOS.', 502);
  }

  if (!upstream.ok) {
    // API mengirim JSON error; teruskan pesannya sebagai teks agar tab baru
    // menampilkan sebab kegagalan, bukan biner rusak.
    const raw = await upstream.text();
    let message = 'Laporan gagal dicetak.';
    try {
      const parsed = JSON.parse(raw) as { error?: { message?: string } };
      if (parsed?.error?.message) message = parsed.error.message;
    } catch {
      /* bukan JSON: pakai pesan umum */
    }
    return text(message, upstream.status);
  }

  const headers = new Headers();
  headers.set(
    'Content-Type',
    upstream.headers.get('content-type') ?? 'application/pdf',
  );

  const disposition = upstream.headers.get('content-disposition');
  if (disposition) headers.set('Content-Disposition', disposition);

  const length = upstream.headers.get('content-length');
  if (length) headers.set('Content-Length', length);

  headers.set('Cache-Control', 'private, max-age=0, must-revalidate');
  headers.set('X-Content-Type-Options', 'nosniff');

  return new Response(upstream.body, { status: 200, headers });
};
