/**
 * Bentuk payload modul disposisi (sisi server).
 *
 * Sumber kontrak: sinos/application/controllers/api/Disposisi.php
 *   GET    /disposisi            → baris daftar (lihat DisposisiRow)
 *   GET    /disposisi/{id}       → detail + surat{} + aksi{} (menandai dibaca)
 *   GET    /disposisi/form/{id}  → data form modal (bukan HTML)
 *   POST   /disposisi            → kirim disposisi
 *   POST   /disposisi/{id}/arsip → arsipkan SURAT (id = surmas_id)
 *   DELETE /disposisi/{id}       → hapus disposisi
 *
 * Catatan: `POST /disposisi/{id}/arsip` memakai **surmas_id**, bukan id
 * disposisi — sama dengan rute CI3 `disposisi/arsipkan/{surmas_id}`.
 */

export type TanggalMeta = { iso: string | null; teks: string };

export type FileMeta = {
  state: "none" | "pending" | "ready";
  path: string | null;
  label: string;
};

/** Gerbang aksi per baris, dihitung server dari peran + posisi disposisi. */
export type DisposisiAksi = {
  can_view: boolean;
  can_archive: boolean;
  can_delete: boolean;
  can_forward: boolean;
};

export type DisposisiRow = {
  id: number;
  surmas_id: number;
  no_surat: string;
  no_agenda: string;
  tgl_surat: TanggalMeta;
  tgl_diterima: TanggalMeta;
  pengirim: string;
  perihal: string;
  kepada: string;
  kategori: string;
  nip_tujuan: string | null;
  /** Penerima terakhir, atau "Arsip". */
  lokasi: string;
  instruksi: string | null;
  catatan: string | null;
  dari: string;
  pengirim_nama: string;
  tanggal: TanggalMeta;
  dibaca: boolean;
  dikembalikan: boolean;
  status_surat: string;
  diarsipkan: boolean;
  posisi_di_operator: boolean;
  /** "Sudah diarsipkan" | "Dikembalikan" | "Dibaca" | "Belum dibaca". */
  status: string;
  tone: string;
  file: FileMeta;
  aksi: DisposisiAksi;
};

export type DisposisiMeta = {
  page: number;
  per_page: number;
  total: number;
  last_page: number;
  /** 'operator' bila pemanggil operator/admin, selain itu 'atas_nama'. */
  lingkup: "operator" | "atas_nama" | string;
  arsip: string | null;
};

/** Ringkasan surat masuk yang menyertai detail disposisi. */
export type SuratRingkas = {
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
  file: FileMeta;
};

/**
 * Posisi disposisi SAAT INI untuk surat (penerima disposisi terakhir).
 *
 * Berbeda dari `nip_tujuan` pada baris detail: baris yang sedang dibuka bisa
 * saja sudah diteruskan ke orang lain. `is_pemilik` dihitung server sehingga
 * UI tidak perlu menebak dari NIP sendiri.
 */
export type DisposisiPosisi = {
  nip: string | null;
  kepada: string;
  kategori: string;
  tanggal: TanggalMeta;
  is_pemilik: boolean;
};

export type DisposisiDetail = {
  id: number;
  surmas_id: number;
  kepada: string;
  kategori: string;
  nip_tujuan: string | null;
  instruksi: string | null;
  catatan: string | null;
  dari: string;
  tanggal: TanggalMeta;
  dibaca: boolean;
  /** TRUE hanya pada request yang benar-benar membalik status (efek GET). */
  ditandai_dibaca: boolean;
  dikembalikan: boolean;
  tgl_dikembalikan: TanggalMeta;
  status: string;
  tone: string;
  /** Posisi disposisi saat ini; null bila surat belum pernah didisposisikan. */
  posisi: DisposisiPosisi | null;
  surat: SuratRingkas | null;
  aksi: DisposisiAksi;
};

/** Penerima aktif pada form disposisi ("Jabatan (Nama)"). */
export type Penerima = {
  id: number;
  nip: string;
  nama: string;
  jabatan: string;
  label: string;
};

export type InstruksiOpsi = { value: string; label: string };

export type RiwayatDisposisi = {
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

export type DisposisiFormPayload = {
  surat: SuratRingkas;
  penerima: Penerima[];
  arsip: InstruksiOpsi;
  instruksi: InstruksiOpsi[];
  riwayat: RiwayatDisposisi[];
  peran: {
    nip: string;
    is_admin: boolean;
    is_operator: boolean;
    can_send: boolean;
  };
};

/**
 * Teks keadaan kosong, verbatim dari sinos/application/views/disposisi/index.php:15-18.
 * `lingkup` berasal dari `meta.lingkup` API, bukan dari tebakan peran lokal.
 */
export function emptyDescription(lingkup: string): string {
  return lingkup === "operator"
    ? "Belum ada surat yang sedang didisposisikan."
    : "Tidak ada surat yang posisi disposisinya ada pada Anda.";
}

/**
 * Subjudul halaman, verbatim dari kartu CI3 (index.php:5-7). Controller CI3
 * memakai kalimat lain untuk topbar; brief meminta teks view, jadi yang ini
 * yang dipakai.
 */
export function pageSubtitle(lingkup: string): string {
  return lingkup === "operator"
    ? "Seluruh surat yang sedang didisposisi (belum diarsipkan)."
    : "Surat yang posisi disposisinya sedang ada pada Anda.";
}
