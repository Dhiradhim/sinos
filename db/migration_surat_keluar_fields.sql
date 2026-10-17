-- =============================================================================
-- SINOS - Penyesuaian field Surat Keluar (ambil & sisip nomor)
-- =============================================================================
-- Menambah kolom yang dibutuhkan form baru:
--
--   penandatangan_nip  : NIP pejabat penanda tangan (dari pegawai_db.pegawai)
--   penandatangan_nama : snapshot nama + jabatan saat nomor dibuat, mis.
--                        "Dahron, S.Ag., M.S.I. [KPA]". Disimpan sebagai
--                        snapshot agar riwayat surat tetap terbaca walau
--                        pegawai berganti jabatan atau dihapus.
--   kode_penetapan     : kode jenis naskah dinas (SK, SP, ST, SKET, PENG, UND, -)
--   jumlah             : jumlah nomor yang diambil dalam satu transaksi
--
-- Semua kolom NULL-able agar 5.828 baris lama tetap valid.
--
-- Aman diulang: gunakan pengecekan information_schema sebelum ADD COLUMN.
-- =============================================================================

-- ---------------------------------------------------------------- penandatangan_nip
SET @sql = (
  SELECT IF(
    COUNT(*) = 0,
    'ALTER TABLE `nosur` ADD COLUMN `penandatangan_nip` varchar(30) DEFAULT NULL AFTER `nip`',
    'SELECT "kolom penandatangan_nip sudah ada" AS info'
  )
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME   = 'nosur'
    AND COLUMN_NAME  = 'penandatangan_nip'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- --------------------------------------------------------------- penandatangan_nama
SET @sql = (
  SELECT IF(
    COUNT(*) = 0,
    'ALTER TABLE `nosur` ADD COLUMN `penandatangan_nama` varchar(150) DEFAULT NULL AFTER `penandatangan_nip`',
    'SELECT "kolom penandatangan_nama sudah ada" AS info'
  )
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME   = 'nosur'
    AND COLUMN_NAME  = 'penandatangan_nama'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ------------------------------------------------------------------- kode_penetapan
SET @sql = (
  SELECT IF(
    COUNT(*) = 0,
    'ALTER TABLE `nosur` ADD COLUMN `kode_penetapan` varchar(10) DEFAULT NULL AFTER `kj`',
    'SELECT "kolom kode_penetapan sudah ada" AS info'
  )
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME   = 'nosur'
    AND COLUMN_NAME  = 'kode_penetapan'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- -------------------------------------------------------------------------- jumlah
SET @sql = (
  SELECT IF(
    COUNT(*) = 0,
    'ALTER TABLE `nosur` ADD COLUMN `jumlah` smallint unsigned NOT NULL DEFAULT 1 AFTER `file`',
    'SELECT "kolom jumlah sudah ada" AS info'
  )
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME   = 'nosur'
    AND COLUMN_NAME  = 'jumlah'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ------------------------------------------------------------------------- indeks
-- Mempercepat pencarian nomor terakhir pada satu tanggal (dipakai penomoran
-- dan penentuan huruf sisip).
SET @sql = (
  SELECT IF(
    COUNT(*) = 0,
    'ALTER TABLE `nosur` ADD KEY `idx_tanggal_no_urut` (`tanggal`, `no_urut`)',
    'SELECT "indeks idx_tanggal_no_urut sudah ada" AS info'
  )
  FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME   = 'nosur'
    AND INDEX_NAME   = 'idx_tanggal_no_urut'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- =============================================================================
-- PERLEBAR KOLOM `kode`
-- =============================================================================
-- Kolom lama `varchar(3)` hanya cukup untuk kode pendek seperti 'KU' atau 'HK'.
-- Kode klasifikasi yang dipakai form baru (dari `ref_klasifikasi`) panjangnya
-- sampai 8 karakter, mis. 'KU1.1.2' atau 'HK1.2.5'. Tanpa perubahan ini,
-- penyimpanan gagal dengan "Data too long for column 'kode'" atau terpotong.
--
-- `kode_penetapan` (SK/SP/ST/...) disimpan terpisah, sehingga `kode` tetap
-- hanya berisi kode klasifikasi.
ALTER TABLE `nosur`
  MODIFY `kode` varchar(20) NOT NULL DEFAULT '';

-- =============================================================================
-- VERIFIKASI
-- =============================================================================
-- SHOW COLUMNS FROM nosur;
-- SELECT MAX(CHAR_LENGTH(kode)) FROM nosur;
-- SELECT COUNT(*) FROM nosur WHERE penandatangan_nip IS NULL;  -- baris lama
