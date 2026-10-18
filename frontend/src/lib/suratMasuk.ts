/**
 * Helper modul surat masuk (sisi server).
 *
 * Dua hal yang dijamin di sini:
 *
 * 1. **Nama field**. `FORM_FIELDS` adalah satu-satunya daftar field yang
 *    diteruskan ke API. Kontraknya dari `api_rules_surat_masuk()`
 *    (sinos/application/helpers/api_validation_helper.php:45). Field di luar
 *    daftar ini dibuang, sehingga `_method` (kontrol transport) tidak pernah
 *    ikut tersimpan.
 *
 * 2. **Transport update**. PHP tidak mengisi `$_FILES` pada request PUT, jadi
 *    update multipart dikirim sebagai POST + `_method=PUT` — bentuk yang
 *    memang didukung `Surat_masuk::method_efektif()`
 *    (sinos/application/controllers/api/Surat_masuk.php:573).
 */
import { apiFetch, type ApiEnvelope } from "./api";

/** Field yang boleh diteruskan ke API, urut seperti view CI3. */
export const FORM_FIELDS = [
  "pengirim",
  "pengolah",
  "no_surat",
  "no_agenda",
  "tgl_surat",
  "tgl_diterima",
  "kode",
  "perihal",
  "keterangan",
] as const;

export type SuratMasukInput = Record<string, string>;

export type FileMeta = {
  state: "none" | "pending" | "ready";
  path: string | null;
  label: string;
};

export type TanggalMeta = { iso: string | null; teks: string };

export type DisposisiRingkas = {
  status: string;
  tone: string;
  lokasi: string | null;
  dibaca: boolean;
  dikembalikan: boolean;
  diarsipkan: boolean;
  instruksi: string | null;
};

export type SuratMasukRow = {
  id: number;
  kode: string;
  no_surat: string;
  no_agenda: string;
  tgl_surat: TanggalMeta;
  tgl_diterima: TanggalMeta;
  pengirim: string;
  perihal: string;
  pengolah: string;
  status: string;
  diarsipkan: boolean;
  file: FileMeta;
  disposisi: DisposisiRingkas;
  can_send: boolean;
};

export type RiwayatItem = {
  id: number;
  kepada: string;
  kategori: string;
  nip_tujuan: string | null;
  instruksi: string | null;
  catatan: string | null;
  dari: string;
  tanggal: TanggalMeta;
  dibaca: boolean;
  dikembalikan: boolean;
};

export type Posisi = {
  id: number;
  kepada: string;
  kategori: string;
  instruksi: string | null;
  tanggal: TanggalMeta;
  dibaca: boolean;
  dikembalikan: boolean;
  status: string;
  tone: string;
};

export type SuratMasukAksi = {
  can_view: boolean;
  can_send: boolean;
  can_edit: boolean;
  can_print_disposisi: boolean;
  can_arsipkan: boolean;
  can_batal_arsip: boolean;
  can_delete: boolean;
};

export type SuratMasukDetail = {
  id: number;
  kode: string;
  no_surat: string;
  no_agenda: string;
  tgl_surat: TanggalMeta;
  tgl_diterima: TanggalMeta;
  pengirim: string;
  perihal: string;
  pengolah: string;
  keterangan: string;
  status: string;
  diarsipkan: boolean;
  file: FileMeta;
  posisi: Posisi | null;
  riwayat: RiwayatItem[];
  aksi: SuratMasukAksi;
};

export type PageMeta = {
  page: number;
  per_page: number;
  total: number;
  last_page: number;
};

/** Nilai form apa adanya, hanya field yang dikenal kontrak. */
export function pickFields(form: FormData): SuratMasukInput {
  const out: SuratMasukInput = {};
  for (const field of FORM_FIELDS) {
    const value = form.get(field);
    out[field] = typeof value === "string" ? value : "";
  }
  return out;
}

/**
 * Teruskan form ke API.
 *
 * `id` kosong = create (`POST /surat-masuk`). Bila diisi = update
 * (`POST /surat-masuk/{id}` + `_method=PUT`), karena berkas ikut dikirim.
 */
export async function submitSuratMasuk(
  form: FormData,
  token: string | null,
  id?: number | string | null,
): Promise<ApiEnvelope<SuratMasukDetail>> {
  const body = new FormData();

  for (const field of FORM_FIELDS) {
    const value = form.get(field);
    if (typeof value === "string") body.set(field, value);
  }

  // Berkas hanya disertakan bila benar-benar dipilih. Nama kosong berarti
  // "pertahankan berkas lama" pada mode edit.
  const file = form.get("file");
  if (file instanceof File && file.size > 0) {
    body.set("file", file, file.name);
  }

  const isEdit = id !== undefined && id !== null && String(id) !== "";
  if (isEdit) body.set("_method", "PUT");

  const path = isEdit ? `/surat-masuk/${String(id)}` : "/surat-masuk";

  return apiFetch<SuratMasukDetail>(path, { method: "POST", body, token });
}

/** Peta `field => pesan` dari envelope error, atau null. */
export function errorFields(
  error: { fields?: Record<string, string> } | null,
): Record<string, string> | null {
  if (!error || !error.fields) return null;
  return Object.keys(error.fields).length > 0 ? error.fields : null;
}
