/**
 * Tampilan `dd/mm/yyyy` untuk SEMUA `<input type="date">`.
 *
 * KENAPA overlay, bukan `placeholder` atau `type="text"`
 * - `placeholder` diabaikan browser pada `<input type="date">`; teks yang
 *   tampil adalah segmen native mengikuti locale browser ("mm/dd/yyyy" pada
 *   locale en-US), dan itu tidak dapat diubah lewat CSS/atribut.
 * - `type="text"` + datepicker pihak ketiga akan mengubah nilai yang dikirim
 *   ke server menjadi `dd/mm/yyyy`, padahal kontrak API dan seluruh form
 *   memakai `YYYY-MM-DD`.
 *
 * Solusi: `type="date"` dipertahankan (nilai tetap ISO), teks native
 * disembunyikan saat tidak fokus, lalu overlay menampilkan nilai dalam
 * `dd/mm/yyyy`. Bila kosong, overlay menampilkan TANGGAL HARI INI sebagai
 * placeholder (abu-abu), sama seperti nilai awal pada halaman /laporan.
 *
 * Perilaku:
 *   - kosong & tidak fokus → "21/10/2026" (placeholder, warna subtle);
 *   - terisi & tidak fokus → nilai sebenarnya dalam "21/10/2026";
 *   - fokus                 → overlay hilang, segmen native tampil sehingga
 *                             pengguna dapat mengetik/memilih dari kalender.
 */

import { TIMEZONE } from "./format";

/** "21/10/2026" menurut zona aplikasi (UTC+8), bukan zona mesin. */
export function todaySlash(): string {
  return isoToSlash(todayISO());
}

/** Tanggal hari ini (YYYY-MM-DD) menurut zona aplikasi. */
function todayISO(): string {
  const parts = new Intl.DateTimeFormat("en-CA", {
    timeZone: TIMEZONE,
    day: "2-digit",
    month: "2-digit",
    year: "numeric",
  }).formatToParts(new Date());

  const pick = (type: string): string =>
    parts.find((part) => part.type === type)?.value ?? "";

  return `${pick("year")}-${pick("month")}-${pick("day")}`;
}

/** "2026-10-21" → "21/10/2026". Nilai tak dikenal dikembalikan apa adanya. */
export function isoToSlash(iso: string): string {
  const m = /^(\d{4})-(\d{2})-(\d{2})/.exec(iso.trim());
  return m ? `${m[3]}/${m[2]}/${m[1]}` : iso;
}

const WRAPPER_CLASS = "date-field";
const DISPLAY_CLASS = "date-display";
const EMPTY_CLASS = "is-empty";

/** Satu input tanggal: elemen overlay + teks placeholder hari ini. */
interface Field {
  input: HTMLInputElement;
  display: HTMLSpanElement;
  placeholder: string;
}

const fields: Field[] = [];

/** Perbarui overlay sesuai nilai input saat ini. */
function sync(field: Field): void {
  const { input, display, placeholder } = field;
  const value = input.value;

  input.classList.toggle(EMPTY_CLASS, value === "");
  display.textContent = value === "" ? placeholder : isoToSlash(value);
  display.classList.toggle("is-placeholder", value === "");
}

/** Pasang overlay pada satu input tanggal. */
function decorate(input: HTMLInputElement, placeholder: string): void {
  const parent = input.parentElement;
  if (!parent) return;

  // Bungkus input dengan span relatif agar overlay mudah diposisikan.
  // `display: block` menjaga lebar tetap 100% seperti `.input`.
  const wrapper = document.createElement("span");
  wrapper.className = WRAPPER_CLASS;
  parent.insertBefore(wrapper, input);
  wrapper.appendChild(input);

  const display = document.createElement("span");
  display.className = DISPLAY_CLASS;
  display.setAttribute("aria-hidden", "true");
  wrapper.appendChild(display);

  const field: Field = { input, display, placeholder };
  fields.push(field);

  // `input` menangkap ketikan, `change` menangkap pilihan dari kalender,
  // `focus`/`blur` mengatur kapan overlay boleh tampil.
  input.addEventListener("input", () => sync(field));
  input.addEventListener("change", () => sync(field));
  input.addEventListener("focus", () => sync(field));
  input.addEventListener("blur", () => sync(field));

  sync(field);
}

/**
 * Terapkan tampilan `dd/mm/yyyy` (dengan tanggal hari ini sebagai placeholder)
 * ke seluruh input tanggal pada dokumen. Aman dipanggil berulang.
 */
export function initDateFields(root: ParentNode = document): void {
  const placeholder = todaySlash();

  root.querySelectorAll<HTMLInputElement>('input[type="date"]').forEach((input) => {
    // `data-no-date-display` untuk input yang sengaja dibiarkan apa adanya.
    if (input.dataset.noDateDisplay !== undefined) return;
    if (input.dataset.dateDisplay === "on") return;
    input.dataset.dateDisplay = "on";
    decorate(input, placeholder);
  });
}

/** Sinkronkan ulang seluruh field (mis. setelah form diisi lewat skrip). */
export function refreshDateFields(): void {
  fields.forEach(sync);
}
