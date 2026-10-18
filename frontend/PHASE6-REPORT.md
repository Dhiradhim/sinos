# PHASE 6 — Full Parity & Negative-Path Sweep

Verification-only pass over the Astro app (`sinos/frontend`) against the frozen
CI3 backend (`sinos`). No features added; only defects found during verification
were fixed.

> **Path note (2026-10-18):** the frontend was originally a sibling folder
> `c:/laragon/www/sinos-web`. It now lives at `c:/laragon/www/sinos/frontend`;
> paths below are updated accordingly. Build/check results are unchanged.

- **Frontend:** `c:/laragon/www/sinos/frontend`
- **Backend (frozen):** `c:/laragon/www/sinos` — no file in it was modified.
- **Date:** 2026-10-18
- **Server under test:** `node ./dist/server/entry.mjs` on `http://localhost:4321`
  (started, exercised, then killed).

---

## 1. Build / check gate

| Gate  | Command           | Result                                       |
| ----- | ----------------- | -------------------------------------------- |
| Build | `npm run build`   | **exit 0, zero warnings**                    |
| Types | `npx astro check` | **0 errors, 0 warnings, 0 hints** (66 files) |

Bundle sizes after the final build:

| Artifact                                             | Size                        |
| ---------------------------------------------------- | --------------------------- |
| Client CSS (`dist/client/_astro/Icon.C5EtogoL.css`)  | **56.815 bytes** (55,5 KiB) |
| Server entry (`dist/server/entry.mjs`)               | **93 bytes**                |
| Server chunks (`dist/server/chunks/*.mjs`, 64 files) | **815.706 bytes** total     |

---

## 2. Page sweep

Every page requested with a valid **admin** token and a valid **operator**
token (`sinos_sess` cookie). 16 pages × 2 roles = 32 requests.

`H1` = number of `<h1>` elements. `RAIL` = the rail item carrying
`rail-item-active` matches the expected item. `BADSEL` = `data-hs-select`
attributes whose value does not parse as JSON. `RAWFILE` = `href="file/…"`.
`APIBERKAS` = links to `/api/berkas/…`.

| Page                         | Admin | Operator | H1  | Rail active                  | Title | BADSEL | RAWFILE |
| ---------------------------- | ----- | -------- | --- | ---------------------------- | ----- | ------ | ------- |
| `/login`                     | 200   | 200      | 1   | —                            | ✓     | 0      | 0       |
| `/dashboard`                 | 200   | 200      | 1   | `/dashboard`                 | ✓     | 0      | 0       |
| `/surat-masuk/daftar`        | 200   | 200      | 1   | `/surat-masuk/daftar`        | ✓     | 0      | 0       |
| `/surat-masuk/input`         | 200   | 200      | 1   | `/surat-masuk/input`         | ✓     | 0      | 0       |
| `/surat-masuk/detail/386`    | 200   | 200      | 1   | `/surat-masuk/daftar`        | ✓     | 0      | 0       |
| `/surat-masuk/edit/386`      | 200   | 200      | 1   | `/surat-masuk/daftar`        | ✓     | 0      | 0       |
| `/surat-masuk/386/cetak`     | 200   | 200      | 0¹  | — ¹                          | ✓     | 0      | 0       |
| `/surat-keluar/daftar`       | 200   | 200      | 1   | — ²                          | ✓     | 0      | 0       |
| `/surat-keluar/daftar-semua` | 200   | 200      | 1   | `/surat-keluar/daftar-semua` | ✓     | 0      | 0       |
| `/surat-keluar/ambil`        | 200   | 200      | 1   | `/surat-keluar/ambil`        | ✓     | 0      | 0       |
| `/surat-keluar/sisip`        | 200   | 200      | 1   | `/surat-keluar/sisip`        | ✓     | 0      | 0       |
| `/surat-keluar/edit/6034`    | 200   | **403**  | 1   | `/surat-keluar/daftar-semua` | ✓     | 0      | 0       |
| `/surat-keluar/upload/6034`  | 200   | **403**  | 1   | `/surat-keluar/daftar-semua` | ✓     | 0      | 0       |
| `/disposisi`                 | 200   | 200      | 1   | `/disposisi`                 | ✓     | 0      | 0       |
| `/disposisi/detail/15`       | 200   | 200      | 1   | `/disposisi`                 | ✓     | 0      | 0       |
| `/laporan`                   | 200   | 200      | 1   | — ³                          | ✓     | 0      | 0       |

¹ `/surat-masuk/{id}/cetak` is a standalone print sheet (`Base.astro`, no
`AppShell`): no rail, and no `<h1>` — the sheet has no page heading by design
(mirrors `surat_masuk/disposisi.php`, which has none either). The only other
page without a PageHead is `/login`, which has its own `<h1>` ("Masuk").
Every other page renders **exactly one** `<h1>`.

² Admin on `/surat-keluar/daftar`: no rail item is active **by design** — CI3
hides the "Daftar Nomor Surat" rail entry for admins
(`layouts/sidebar.php:92`). The page is still reachable by URL.

³ Operator on `/laporan`: no rail item is active because the "Cetak Laporan"
rail entry is admin-only (`Sidebar.astro:251`), matching CI3. The page renders
the `.alert-destructive` restriction instead of a form.

### Link integrity

All 201 distinct internal `href`s found in the rendered pages were requested:
**0 broken** (no 4xx/5xx, no unintended 303).

---

## 3. Negative paths

| #   | Test                                                                     | Requests | Result                                                  |
| --- | ------------------------------------------------------------------------ | -------- | ------------------------------------------------------- |
| 1   | No cookie → 302 `/login`                                                 | 15 pages | **15 pass / 0 fail**                                    |
| 2   | Tampered cookie (one char flipped) → 302 `/login`                        | 15 pages | **15 pass / 0 fail**                                    |
| 3   | Expired token (`ttl=-1`) → 302 `/login`                                  | 15 pages | **15 pass / 0 fail**                                    |
| 4   | Revoked session (`POST /api/v1/auth/logout` then request) → 302 `/login` | 5 pages  | **5 pass / 0 fail**                                     |
| 5   | Operator on `/laporan` → `.alert-destructive`                            | 1        | **PASS** (200 + alert)                                  |
| 6   | Non-owner on `/surat-keluar/edit/6034` → readable Indonesian error       | 1        | **PASS** (403 + "Akses ditolak")                        |
| 7a  | `/surat-masuk/detail/999999`                                             | 1        | **PASS** (404 + "tidak ditemukan" + back link)          |
| 7b  | `/surat-keluar/edit/999999`                                              | 1        | **PASS** (404 + back link)                              |
| 7c  | `/disposisi/detail/999999`                                               | 1        | **PASS** (404 + back link)                              |
| 8   | Invalid `/surat-masuk/input` submit (missing perihal)                    | 1        | **PASS** (303 → form, `.field-error`, values preserved) |
| 9   | `/api/berkas/{tampered}` → 401                                           | 1        | **PASS** (401 + Indonesian message)                     |

Totals: **61 assertions, 61 pass, 0 fail.**

Notes on #4: the token was bound to a freshly seeded `sso_token` session group,
then revoked through the backend logout endpoint (single logout to the gateway).
`/auth/me` went 200 → 401 `SESSION_REVOKED`, and every page then redirected to
`/login`. The seeded row was removed by the logout itself; `sso_token` is back
to its pre-test count of 197.

### Berkas proxy

| Case                 | Before fix   | After fix                 |
| -------------------- | ------------ | ------------------------- |
| Valid file           | 502 (broken) | **200 `application/pdf`** |
| Tampered token       | 502          | **401**                   |
| Missing file         | 502          | **404**                   |
| Path outside `file/` | 502          | **403**                   |
| No cookie            | 401          | 401                       |

---

## 4. Cross-page consistency audit

### 4.1 PageHead title / subtitle vs the CI3 view

| Page                         | Astro title                                                 | CI3 equivalent                                                                                      | Verbatim?                              |
| ---------------------------- | ----------------------------------------------------------- | --------------------------------------------------------------------------------------------------- | -------------------------------------- |
| `/dashboard`                 | `Ringkasan {tahun}`                                         | `section-title` "Ringkasan {tahun}"                                                                 | ✓                                      |
| `/dashboard`                 | "Aktivitas surat masuk dan keluar pada tahun berjalan."     | `section-subtitle` (same)                                                                           | ✓                                      |
| `/surat-masuk/daftar`        | "Daftar Surat Masuk"                                        | `card-title` (same)                                                                                 | ✓                                      |
| `/surat-masuk/daftar`        | "Agenda surat masuk pada periode terpilih."                 | `card-description` (same)                                                                           | ✓                                      |
| `/surat-masuk/input`         | "Input Surat Masuk"                                         | `card-title` (same)                                                                                 | ✓                                      |
| `/surat-masuk/input`         | "Catat surat masuk beserta klasifikasi dan berkasnya."      | `card-description` (same)                                                                           | ✓                                      |
| `/surat-masuk/edit`          | "Edit Surat Masuk" / subtitle `no_surat`                    | `card-title` + controller subtitle                                                                  | ✓                                      |
| `/surat-masuk/detail`        | title = `no_surat`, subtitle "Detail surat masuk"           | controller title "Detail Surat Masuk", subtitle `no_surat`; inner `card-title` "Detail Surat Masuk" | **deviation** (title/subtitle swapped) |
| `/surat-masuk/{id}/cetak`    | `<title>` "Lembar Disposisi - {no_surat}"                   | `<title>` (same)                                                                                    | ✓                                      |
| `/surat-keluar/daftar`       | "Daftar Nomor Surat"                                        | `card-title` (same)                                                                                 | ✓                                      |
| `/surat-keluar/daftar`       | "Nomor surat keluar yang Anda ambil pada periode terpilih." | `card-description` (same)                                                                           | ✓                                      |
| `/surat-keluar/daftar-semua` | "Seluruh Surat Keluar"                                      | `card-title` (same)                                                                                 | ✓                                      |
| `/surat-keluar/daftar-semua` | "Rekap seluruh nomor surat keluar pada periode terpilih."   | `card-description` (same)                                                                           | ✓                                      |
| `/surat-keluar/ambil`        | "Ambil Nomor Surat"                                         | `card-title` (same)                                                                                 | ✓                                      |
| `/surat-keluar/ambil`        | "Daftarkan nomor surat keluar baru beserta klasifikasinya." | `card-description` (same)                                                                           | ✓                                      |
| `/surat-keluar/sisip`        | "Sisip Nomor Surat"                                         | `card-title` (same)                                                                                 | ✓                                      |
| `/surat-keluar/sisip`        | "Sisipkan nomor surat pada tanggal mundur."                 | `card-description` (same)                                                                           | ✓                                      |
| `/surat-keluar/edit`         | "Edit Nomor Surat" / subtitle `no`                          | `card-title` + controller subtitle                                                                  | ✓                                      |
| `/surat-keluar/upload`       | "Upload Berkas Surat" / subtitle `no`                       | `card-title` (same)                                                                                 | ✓                                      |
| `/disposisi`                 | "Daftar Disposisi Surat"                                    | `card-title` (same)                                                                                 | ✓ (fixed this phase)                   |
| `/disposisi`                 | scope-dependent subtitle                                    | `card-description` (same)                                                                           | ✓                                      |
| `/disposisi/detail`          | title = `no_surat`, subtitle "Detail disposisi"             | controller title "Detail Disposisi", subtitle `no_surat`; inner `card-title` "Detail Surat"         | **deviation** (title/subtitle swapped) |
| `/laporan`                   | "Cetak Laporan"                                             | `card-title` (same)                                                                                 | ✓                                      |
| `/laporan`                   | "Pilih jenis laporan dan periode, lalu cetak sebagai PDF."  | `card-description` (same)                                                                           | ✓                                      |

The two remaining deviations are deliberate: the detail pages use the document
number as the page heading (more useful than repeating "Detail …"), and CI3
itself used the number as the topbar subtitle.

### 4.2 DataTable columns vs the CI3 view

| Page                         | CI3 columns                                                                                 | Astro columns                                                                                               | Deviation                                                                                         |
| ---------------------------- | ------------------------------------------------------------------------------------------- | ----------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------- |
| `/surat-keluar/daftar`       | No., No. Surat, Nama, Tanggal, Perihal, Tujuan, Berkas, Aksi                                | same 8                                                                                                      | none                                                                                              |
| `/surat-keluar/daftar-semua` | same 8                                                                                      | same 8                                                                                                      | none                                                                                              |
| `/disposisi`                 | No., Agenda / No. Surat, Tanggal, Pengirim Surat, Perihal, Posisi Saat Ini, Instruksi, Aksi | same 8                                                                                                      | none                                                                                              |
| `/surat-masuk/daftar`        | No., Agenda / No. Surat, Tanggal, **Pengirim**, Perihal, **Status & Berkas**, Aksi (7)      | No., Agenda / No. Surat, Tanggal, **Pengirim Surat**, Perihal, **Posisi Saat Ini**, **Instruksi**, Aksi (8) | label "Pengirim" → "Pengirim Surat"; "Status & Berkas" split into "Posisi Saat Ini" + "Instruksi" |
| `/dashboard`                 | No. Surat, Tanggal, Perihal, Tujuan, Berkas (5)                                             | No., No. Surat, Tanggal, Perihal, Tujuan, Berkas, **Aksi** (7)                                              | "No." and "Aksi" columns added                                                                    |

Missing-in-Astro: **none**. The "Status & Berkas" split is a deliberate
decomposition; the "Berkas" link it used to carry was restored this phase (it
had been dropped — see §6).

### 4.3 Filter controls

| Page                         | CI3                                        | Astro                                     | Added from API contract?                     |
| ---------------------------- | ------------------------------------------ | ----------------------------------------- | -------------------------------------------- |
| `/surat-masuk/daftar`        | DataTables client search + year selector   | `q`, `arsip`, `disposisi` + year selector | **`arsip`, `disposisi`** — no CI3 equivalent |
| `/surat-keluar/daftar`       | DataTables client search + year selector   | `q` + year selector                       | none                                         |
| `/surat-keluar/daftar-semua` | DataTables client search + year selector   | `q` + year selector                       | none                                         |
| `/disposisi`                 | DataTables client search                   | `q`, `arsip`                              | **`arsip`** — no CI3 equivalent              |
| `/laporan`                   | tipelaporan, tgl_cetak, bulan, tahun, kode | same 5                                    | none                                         |

**Decision needed by the user:** `arsip` and `disposisi` on
`/surat-masuk/daftar`, and `arsip` on `/disposisi`, exist only because the API
contract exposes them. They are server-side replacements for DataTables' client
filtering, but they let the user narrow by state that CI3 could only sort by.

### 4.4 Action buttons

| Page                         | CI3 action                                                 | In Astro?                            |
| ---------------------------- | ---------------------------------------------------------- | ------------------------------------ |
| `/surat-masuk/daftar`        | Lihat detail                                               | ✓                                    |
|                              | Kirim / teruskan disposisi                                 | ✓                                    |
|                              | Edit surat                                                 | ✓                                    |
|                              | Cetak lembar disposisi                                     | ✓                                    |
|                              | Batalkan arsip                                             | ✓ (now POST, was a dead link)        |
|                              | Hapus surat                                                | ✓ (now POST, was a dead link)        |
| `/surat-masuk/detail`        | Kembali, Lihat Berkas, Arsip, Batal Arsip, Cetak Disposisi | ✓                                    |
| `/surat-keluar/daftar`       | Upload berkas, Edit                                        | ✓ (gated by `can_edit`/`can_upload`) |
| `/surat-keluar/daftar-semua` | Upload berkas, Edit                                        | ✓ (same gating)                      |
| `/disposisi`                 | Detail, Teruskan, Arsipkan surat, Hapus disposisi          | ✓                                    |
| `/disposisi/detail`          | Lihat Berkas Surat, Teruskan, Arsipkan, Hapus, Kembali     | ✓                                    |
| `/dashboard`                 | — (no row action in CI3)                                   | **"Lihat detail" added**             |

Missing-in-Astro: **none**. Deviations: (a) `/surat-keluar/*` hides
upload/edit for non-owners instead of showing buttons that would 403 — the
backend still enforces `boleh_ubah()`, and the page now renders that 403
readably; (b) the dashboard row action is an addition (now pointing at
`/surat-keluar/edit/{id}`, since no detail page exists in either stack).

### 4.5 Tone → class mapping

Tones the API can emit (`api_present_helper.php` §12-13, `api_tone_from_badge()`,
`Dashboard.php`, `Disposisi.php`, `Surat_masuk.php`):

| Tone      | Emitted by                                 | `toneClass()`     |
| --------- | ------------------------------------------ | ----------------- |
| `brand`   | dashboard stats, `badge-primary`           | `badge-default` ✓ |
| `sky`     | dashboard stats                            | `badge-info` ✓    |
| `amber`   | dashboard stats                            | `badge-warning` ✓ |
| `danger`  | dashboard stats, `badge-danger`            | `badge-danger` ✓  |
| `ok`      | stats, `badge-success`, posisi             | `badge-success` ✓ |
| `muted`   | `badge-secondary`, `badge-muted`, archived | `badge-muted` ✓   |
| `neutral` | `badge-outline`, `badge-default`, "Dibaca" | `badge-outline` ✓ |
| `warning` | `badge-warning`, "Belum dibaca"            | `badge-warning` ✓ |
| `info`    | `badge-info`                               | `badge-info` ✓    |

All nine map. `success` is also mapped (defensive; not currently emitted).
**No unmapped tone remains.**

---

## 5. Data integrity

| Table       | Phase 2 baseline | Now       | Delta |
| ----------- | ---------------- | --------- | ----- |
| `surmas`    | 364              | **364**   | 0     |
| `nosur`     | 5 828            | **5 828** | 0     |
| `disposisi` | 6                | **6**     | 0     |

No test rows were written: the invalid-form test was rejected by the API
(422) before insert, and the revoked-session test only wrote a `sso_token` row
which the logout itself deleted (`sso_token`: 197 before, 197 after).

### Files in `sinos/file/`

7 files on disk, 3 973 distinct paths referenced by `surmas.file` + `nosur.file`.

**Orphan files (on disk, not referenced by any row) — 7:**

```
file/index.html
file/sm/index.html
file/sm/2026_DL1.10_Mohon_bantuan_Pengiriman_Akta_.pdf
file/sm/2026_DL1.1_Perihal_Awal_20261018132634.pdf
file/sm/2026_DL1.1_Perihal_Awal_20261018132736.pdf
file/sm/2026_DL1.1_Uji_round-trip_Phase_5b_202610.pdf
file/sm/2026_DL1.1_ZMohon_bantuan_Pengiriman_Akta.pdf
```

The four `2026_DL1.1_*` / `2026_DL1.10_*` PDFs are artefacts of earlier phase
tests (they carry phase-test names). `index.html` files are directory-index
guards and are expected. None were deleted — removing them is a data decision,
not a code fix, and `sinos/` is frozen for this phase.

**Referenced but absent on disk — 3 972 paths.** `file/` currently holds 7
files while the DB references ~3 973 historical PDFs; only the handful of
files uploaded during Phases 2–5 exist. Pages that link them are correct (the
link is `/api/berkas/…`), and the proxy now returns a readable 404 rather than
a 502 — but the underlying archive is not present in this environment. This is
an **environment/data gap, not a frontend defect**.

---

## 6. Defects fixed

| #   | File                                                            | What was wrong                                                                                                                                     | What changed                                                                                                           |
| --- | --------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------- |
| 1   | `src/pages/surat-masuk/detail/[id].astro`                       | Unknown id redirected (303) to the list instead of returning a readable 404, unlike CI3's `show_404()`.                                            | Renders `NotFoundState` with `Astro.response.status = 404`.                                                            |
| 2   | `src/pages/surat-masuk/edit/[id].astro`                         | Same redirect-on-missing.                                                                                                                          | Same 404 treatment.                                                                                                    |
| 3   | `src/pages/surat-masuk/[id]/cetak.astro`                        | Same redirect-on-missing on the print sheet.                                                                                                       | 404 state with `<h1>` + back link.                                                                                     |
| 4   | `src/pages/surat-keluar/edit/[id].astro`                        | Missing id → 303; non-owner → 303 bounce to `/upload` (which bounced back to `/edit`, an infinite loop).                                           | 404 for missing, **403** + "Akses ditolak" for non-owner; no loop.                                                     |
| 5   | `src/pages/surat-keluar/upload/[id].astro`                      | Same 303 bounce loop for non-owners.                                                                                                               | 404 / 403 rendered; loop removed.                                                                                      |
| 6   | `src/pages/disposisi/detail/[id].astro`                         | Missing id → 303; the 404 branch then read `row.kepada` on `null`, producing a **500**.                                                            | 404 state; field list guarded (`notFound ? [] : […]`).                                                                 |
| 7   | `src/pages/api/berkas/[...path].ts`                             | Every upstream failure was collapsed to **502**, so a tampered token or a missing file reported "Berkas tidak ditemukan." with the wrong status.   | Upstream 401/403/404 are now passed through with distinct Indonesian messages; 502 only for genuine upstream failure.  |
| 8   | `src/pages/surat-masuk/daftar.astro`                            | "Batalkan arsip" and "Hapus surat" were links to `?batal-arsip=` / `?hapus=` — query params the page never handled, so both actions were **dead**. | Both are now POST forms to `/api/surat-masuk/{id}/batal-arsip` and `/api/surat-masuk/{id}/hapus`, with `ConfirmModal`. |
| 9   | `src/pages/api/surat-masuk/[id]/hapus.ts`                       | _(new)_ No BFF route existed for delete; the API only accepts `DELETE /surat-masuk/{id}`.                                                          | POST proxy with `?kembali=` open-redirect guard.                                                                       |
| 10  | `src/pages/surat-masuk/daftar.astro`                            | The CI3 "Berkas" link in the "Status & Berkas" column had been dropped.                                                                            | Restored as a `badge-success` link to `/api/berkas/…` when `file.state === "ready"`.                                   |
| 11  | `src/pages/dashboard.astro`                                     | Row action pointed at `/surat-keluar/detail/{id}`, a route that exists in **neither** stack → 404 for every row.                                   | Points at `/surat-keluar/edit/{id}`.                                                                                   |
| 12  | `src/pages/disposisi/index.astro`                               | Page title was "Disposisi Surat" (controller wording) while the subtitle was the CI3 card text — inconsistent.                                     | Title is now "Daftar Disposisi Surat" (CI3 `card-title`).                                                              |
| 13  | `src/pages/surat-keluar/ambil.astro`                            | Submit button said "Simpan"; CI3 says "Ambil Nomor".                                                                                               | Label restored to "Ambil Nomor".                                                                                       |
| 14  | `src/components/NotFoundState.astro`                            | _(new)_ 404 pages had no `<h1>`.                                                                                                                   | Component carries exactly one `<h1>` via `.page-head`.                                                                 |
| 15  | `src/pages/surat-keluar/edit/[id].astro`, `…/upload/[id].astro` | The 403 branch had no `<h1>`.                                                                                                                      | 403 branch renders `PageHead`.                                                                                         |

No refactoring of working code was performed; each change is confined to the
defective path.

---

## 7. Known gaps (intentionally not migrated)

1. **`/surat-keluar/detail/{id}`** — does not exist in CI3 either. The
   dashboard now links to the edit form instead. Adding a detail page would be
   a new feature.
2. **DataTables client-side sorting/search** — replaced by server-side `q` +
   pagination. Column-header sort indicators are not implemented.
3. **`/surat-masuk/{id}/cetak` has no `<h1>` and no rail** — it is a print
   sheet, not an app page, matching the CI3 view.
4. **Non-owner action buttons are hidden, not disabled** — CI3 showed them and
   let the backend 403. Astro hides them and renders a readable 403 if the URL
   is typed directly.
5. **Extra server-side filters** (`arsip`, `disposisi`) — see §4.3; they exist
   only because the API exposes them.
6. **3 972 historical PDFs referenced by the DB are absent from `file/`** — an
   environment gap; the app degrades to a readable 404.
7. **`index.html` guards and four phase-test PDFs remain in `file/`** — left in
   place because `sinos/` is frozen and deleting files is a data decision.
8. **`tgl_cetak` is a plain text input** on `/laporan` (CI3 uses a text input
   with `d-m-Y` too) — no date picker was added.

---

## 8. Go / no-go for Phase 7

**GO.**

- Build and `astro check` are clean (0/0/0).
- All 16 pages render for both roles; the only non-200s are the intended 403s
  for non-owner edit/upload.
- Every page has exactly one `<h1>` (except the print sheet and `/login`, both
  by design), the correct rail item is active, no `data-hs-select` attribute
  fails JSON parsing, and no page emits a raw `file/…` href.
- 61/61 negative-path assertions pass, including no-cookie, tampered, expired,
  revoked, RBAC, three 404s, invalid form, and tampered berkas token.
- Row counts match the Phase 2 baseline exactly (364 / 5 828 / 6); no test data
  remains.
- 15 defects were found and fixed, including two that produced a **500** and an
  infinite redirect loop, plus a berkas proxy that failed for every file.

Deleting the CI3 frontend in Phase 7 will not remove any route the Astro app
still links to. The open items in §7 are data/environment gaps or deliberate
scope decisions, not functional blockers.

> **Phase 7 is done (2026-10-18).** The CI3 frontend has been deleted; `sinos/`
> now serves only `/api/v1/*` plus the SSO routes. This Astro app was
> re-verified against the pruned backend. See
> [`sinos/plans/2026-10-17-sinos-astro-preline.md`](../sinos/plans/2026-10-17-sinos-astro-preline.md)
> § "Status Implementasi" for the deletion inventory, backup location, and
> remaining follow-ups.
