/**
 * Pembungkus fetch ke SINOS API (CI3).
 *
 * Kontrak server: setiap respons JSON berbentuk { data, meta, error }.
 * Fungsi ini TIDAK PERNAH melempar error HTTP — pemanggil selalu menerima
 * objek hasil dan memutuskan sendiri apa yang ditampilkan.
 */

/**
 * Basis URL API. Diekspor agar route yang perlu menerima/mengirim biner
 * (mis. proxy PDF laporan) dapat memanggil `fetch()` mentah tanpa melalui
 * `apiFetch()`, yang selalu mem-parse JSON.
 */
export const API_BASE = (import.meta.env.SINOS_API_BASE ?? 'http://localhost/sinos/api/v1').replace(/\/+$/, '');

export type ApiEnvelope<T> = {
  ok: boolean;
  data: T | null;
  meta: Record<string, unknown> | null;
  error: ApiError | null;
  status: number;
};

export type ApiError = {
  code: string;
  message: string;
  fields?: Record<string, string>;
};

export type ApiQuery = Record<string, string | number | boolean | null | undefined>;

/** Buang nilai kosong/null agar URL tidak berisi `?tahun=&q=`. */
export function apiQuery(params: ApiQuery = {}): string {
  const search = new URLSearchParams();
  for (const [key, value] of Object.entries(params)) {
    if (value === null || value === undefined) continue;
    const str = String(value).trim();
    if (str === '') continue;
    search.set(key, str);
  }
  const qs = search.toString();
  return qs ? `?${qs}` : '';
}

export type ApiFetchOptions = {
  method?: string;
  body?: unknown;
  token?: string | null;
  headers?: Record<string, string>;
  query?: ApiQuery;
};

export async function apiFetch<T = unknown>(
  path: string,
  options: ApiFetchOptions = {},
): Promise<ApiEnvelope<T>> {
  const { method = 'GET', body, token, headers = {}, query } = options;

  const url = `${API_BASE}/${path.replace(/^\/+/, '')}${apiQuery(query)}`;
  const requestHeaders: Record<string, string> = { Accept: 'application/json', ...headers };

  if (token) requestHeaders.Authorization = `Bearer ${token}`;

  let payload: BodyInit | undefined;

  if (body instanceof FormData) {
    // Biarkan runtime menetapkan boundary multipart; jangan set Content-Type.
    payload = body;
  } else if (body !== undefined && body !== null) {
    requestHeaders['Content-Type'] = 'application/json';
    payload = JSON.stringify(body);
  }

  let response: Response;
  try {
    response = await fetch(url, { method, headers: requestHeaders, body: payload });
  } catch (cause) {
    // Backend mati / DNS gagal: normalkan menjadi envelope error.
    return {
      ok: false,
      data: null,
      meta: null,
      error: {
        code: 'NETWORK_ERROR',
        message: 'Tidak dapat menghubungi server SINOS. Periksa koneksi atau pastikan backend berjalan.',
      },
      status: 0,
    };
  }

  const text = await response.text();
  let parsed: { data?: unknown; meta?: unknown; error?: unknown } | null = null;

  if (text) {
    try {
      parsed = JSON.parse(text) as { data?: unknown; meta?: unknown; error?: unknown };
    } catch {
      parsed = null;
    }
  }

  const error = normalizeError(parsed?.error, response.status);

  return {
    ok: response.ok && error === null,
    data: (parsed?.data ?? null) as T | null,
    meta: (parsed?.meta ?? null) as Record<string, unknown> | null,
    error,
    status: response.status,
  };
}

function normalizeError(raw: unknown, status: number): ApiError | null {
  if (raw && typeof raw === 'object') {
    const obj = raw as Record<string, unknown>;
    return {
      code: typeof obj.code === 'string' ? obj.code : `HTTP_${status}`,
      message: typeof obj.message === 'string' ? obj.message : defaultMessage(status),
      fields:
        obj.fields && typeof obj.fields === 'object'
          ? (obj.fields as Record<string, string>)
          : undefined,
    };
  }

  // Respons non-JSON (mis. 500 HTML dari PHP) tetap menjadi error yang bisa ditampilkan.
  if (status >= 400) {
    return { code: `HTTP_${status}`, message: defaultMessage(status) };
  }

  return null;
}

function defaultMessage(status: number): string {
  if (status === 401) return 'Sesi Anda sudah berakhir. Silakan masuk kembali.';
  if (status === 403) return 'Anda tidak memiliki hak akses untuk tindakan ini.';
  if (status === 404) return 'Data yang diminta tidak ditemukan.';
  if (status >= 500) return 'Terjadi kesalahan pada server SINOS.';
  return 'Permintaan tidak dapat diproses.';
}
