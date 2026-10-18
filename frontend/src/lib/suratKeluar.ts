/**
 * Helper modul surat keluar (sisi server).
 *
 * Sumber kontrak:
 *   - `application/controllers/api/Surat_keluar.php` (payload baca + tulis)
 *   - `application/helpers/api_validation_helper.php` (`api_rules_surat_keluar_*`)
 *   - `application/views/surat_keluar/_form_nomor_fields.php` (nama field)
 *
 * Dua hal yang dijamin di sini:
 *
 * 1. **Nama field**. `FORM_FIELDS` adalah satu-satunya daftar field yang
 *    diteruskan ke API. Field di luar daftar dibuang, sehingga `_method`
 *    (kontrol transport) dan `id_surat` (kontrol UI) tidak pernah ikut.
 *
 * 2. **Transport update**. PHP tidak mengisi `$_FILES` pada request PUT, jadi
 *    update dikirim sebagai POST + `_method=PUT` — bentuk yang didukung
 *    `Surat_keluar::method_efektif()` (sinos/application/controllers/api/
 *    Surat_keluar.php:626).
 *
 * PENOMORAN: klien tidak pernah menghitung atau menampilkan nomor prediksi.
 * `no`, `no_urut`, dan `huruf` hanya dibaca dari respons server.
 */
import { apiFetch, type ApiEnvelope } from "./api";

/** Field form nomor surat, urut seperti `_form_nomor_fields.php`. */
export const FORM_FIELDS = [
  "nip",
  "penandatangan_nip",
  "kode_penetapan",
  "ks",
  "hal",
  "tujuan",
] as const;

/**
 * Pilihan Kode Penetapan.
 *
 * Disalin dari `$config['kode_penetapan']`
 * (sinos/application/config/config.php:229). API belum punya endpoint master
 * untuk daftar ini, jadi nilainya dicerminkan di sini. Urutan array = urutan
 * tampil, sama seperti CI3.
 */
export const KODE_PENETAPAN: { kode: string; label: string }[] = [
  { kode: "-", label: "Surat Lainnya" },
  { kode: "SK", label: "Surat Keputusan" },
  { kode: "SP", label: "Surat Perintah" },
  { kode: "ST", label: "Surat Tugas" },
  { kode: "SKET", label: "Surat Keterangan" },
  { kode: "PENG", label: "Pengumuman" },
  { kode: "UND", label: "Undangan" },
];

export type FileMeta = {
  state: "none" | "pending" | "ready";
  path: string | null;
  label: string;
};

export type TanggalMeta = { iso: string | null; teks: string };

export type SuratKeluarRow = {
  id: number;
  no: string;
  kode: string;
  nip: string;
  nama: string;
  tanggal: TanggalMeta;
  hal: string;
  tujuan: string;
  file: FileMeta;
  /**
   * Daftar (`GET /surat-keluar`) TIDAK mengirim `aksi`. Bila kelak
   * dikirim, nilainya dipakai apa adanya; bila tidak, halaman menurunkan
   * hak akses dari peran + kepemilikan (`row.nip`), sama seperti
   * `Surat_keluar::boleh_ubah()`.
   */
  aksi?: SuratKeluarAksi;
};

export type SuratKeluarAksi = {
  can_view: boolean;
  can_edit: boolean;
  can_upload: boolean;
};

export type SuratKeluarDetail = {
  id: number;
  no: string;
  kode: string;
  no_urut: number;
  huruf: string;
  nip: string;
  nama: string;
  kj: string;
  kode_penetapan: string;
  penandatangan_nip: string;
  penandatangan_nama: string;
  tanggal: TanggalMeta;
  hal: string;
  tujuan: string;
  jumlah: number | null;
  file: FileMeta;
  aksi: SuratKeluarAksi;
};

export type Pengambil = {
  nip: string;
  nama: string;
  jabatan: string;
  kode_jabatan: string;
  label: string;
};

export type PenandatanganItem = {
  nip: string;
  nama: string;
  jabatan: string;
  kode_jabatan: string;
  label: string;
};

export type PenandatanganGrup = {
  grup: string;
  items: PenandatanganItem[];
};

export type Klasifikasi = { kode: string; nama: string };

export type PageMeta = {
  page: number;
  per_page: number;
  total: number;
  last_page: number;
  tahun?: number;
  lingkup?: string;
  catatan?: string;
};

/** Nilai form apa adanya, hanya field yang dikenal kontrak. */
export function pickNomorFields(form: FormData): Record<string, string> {
  const out: Record<string, string> = {};
  for (const field of FORM_FIELDS) {
    const value = form.get(field);
    out[field] = typeof value === "string" ? value : "";
  }
  return out;
}

/** POST /surat-keluar/ambil — nomor melanjutkan nomor terakhir hari ini. */
export function submitAmbil(
  form: FormData,
  token: string | null,
): Promise<ApiEnvelope<Record<string, unknown>>> {
  return apiFetch<Record<string, unknown>>("/surat-keluar/ambil", {
    method: "POST",
    token,
    body: pickNomorFields(form),
  });
}

/** POST /surat-keluar/sisip — tanggal wajib tanggal mundur. */
export function submitSisip(
  form: FormData,
  token: string | null,
): Promise<ApiEnvelope<Record<string, unknown>>> {
  const body = pickNomorFields(form);
  const tanggal = form.get("tanggal");
  body.tanggal = typeof tanggal === "string" ? tanggal : "";

  return apiFetch<Record<string, unknown>>("/surat-keluar/sisip", {
    method: "POST",
    token,
    body,
  });
}

/**
 * PUT /surat-keluar/{id} sebagai POST + `_method=PUT`.
 *
 * `no_urut` dan `huruf` tidak pernah dikirim: server mempertahankannya dan
 * hanya menyusun ulang `no` dari penandatangan + kode yang baru.
 *
 * PENTING — posisi `_method`. `Surat_keluar::method_efektif()`
 * (sinos/application/controllers/api/Surat_keluar.php:626) membaca override
 * dari `X-HTTP-Method-Override`, `$_POST['_method']`, atau
 * `$this->input->get('_method')`. Untuk body JSON, `$_POST` selalu kosong dan
 * `_method` di dalam JSON TIDAK terlihat — akibatnya request tetap dianggap
 * POST dan API membalas 405 `METHOD_NOT_ALLOWED`. Karena itu override
 * dikirim di query string, yang dibaca `input->get()`. Diuji langsung:
 * JSON body + `_method` = 405; `?_method=PUT` = 200.
 */
export function submitUpdate(
  form: FormData,
  token: string | null,
  id: number | string,
): Promise<ApiEnvelope<SuratKeluarDetail>> {
  const body = pickNomorFields(form);
  const tanggal = form.get("tanggal");
  body.tanggal = typeof tanggal === "string" ? tanggal : "";

  return apiFetch<SuratKeluarDetail>(`/surat-keluar/${String(id)}`, {
    method: "POST",
    token,
    body,
    query: { _method: "PUT" },
  });
}

/** POST /surat-keluar/{id}/berkas — multipart, hanya field `file`. */
export function submitBerkas(
  form: FormData,
  token: string | null,
  id: number | string,
): Promise<ApiEnvelope<SuratKeluarDetail>> {
  const body = new FormData();
  const file = form.get("file");
  if (file instanceof File && file.size > 0) {
    body.set("file", file, file.name);
  }

  return apiFetch<SuratKeluarDetail>(`/surat-keluar/${String(id)}/berkas`, {
    method: "POST",
    token,
    body,
  });
}

/** Peta `field => pesan` dari envelope error, atau null. */
export function errorFields(
  error: { fields?: Record<string, string> } | null,
): Record<string, string> | null {
  if (!error || !error.fields) return null;
  return Object.keys(error.fields).length > 0 ? error.fields : null;
}

/**
 * Ringkasan audit `POST /surat-keluar/sisip` dari `meta` respons 201.
 *
 * Nilai mentah meta:
 *   last_no_urut_sebelum : int|null
 *   nomor_terpakai       : [{no_urut, huruf}]
 *   huruf_terpakai       : {no_urut: [huruf, ...]}
 *
 * Diringkas menjadi string agar dapat disimpan di cookie umpan balik
 * (maksimal ~4 KB) lalu ditampilkan sebagai `.alert-success`.
 */
export function summarizeSisipMeta(
  meta: Record<string, unknown> | null,
  nomor: string[],
): Record<string, string> {
  const sebelum = meta?.last_no_urut_sebelum;
  const terpakai = Array.isArray(meta?.nomor_terpakai)
    ? (meta?.nomor_terpakai as { no_urut?: unknown; huruf?: unknown }[])
    : [];

  const nomorTerpakai = terpakai
    .map((item) => `${String(item.no_urut ?? "")}${String(item.huruf ?? "")}`)
    .filter((text) => text.trim() !== "")
    .join(", ");

  const hurufMap =
    meta?.huruf_terpakai && typeof meta.huruf_terpakai === "object"
      ? (meta.huruf_terpakai as Record<string, unknown>)
      : {};

  const hurufTerpakai = Object.entries(hurufMap)
    .map(([urut, huruf]) => {
      const list = Array.isArray(huruf) ? huruf.map(String) : [String(huruf)];
      return `${urut}: ${list.map((h) => (h === "" ? "—" : h)).join(", ")}`;
    })
    .join("; ");

  return {
    nomor: nomor.join(", "),
    last_no_urut_sebelum:
      sebelum === null || sebelum === undefined ? "—" : String(sebelum),
    nomor_terpakai: nomorTerpakai === "" ? "—" : nomorTerpakai,
    huruf_terpakai: hurufTerpakai === "" ? "—" : hurufTerpakai,
  };
}

/** Daftar nomor dari `data.nomor` (array string) pada respons 201. */
export function nomorList(data: unknown): string[] {
  if (!data || typeof data !== "object") return [];
  const value = (data as { nomor?: unknown }).nomor;
  return Array.isArray(value) ? value.map(String) : [];
}
