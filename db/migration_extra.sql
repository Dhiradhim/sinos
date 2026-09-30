-- ============================================================
-- SINOS - Skema referensi (opsional)
-- ============================================================
-- CATATAN: Database `sinos` yang sudah ada umumnya SUDAH memiliki
-- tabel: user, nosur, surmas, jabatan, ref_klasifikasi.
-- File ini hanya untuk instalasi BARU / referensi struktur.
-- Jalankan setelah db/sinos.sql bila perlu. Aman diulang (IF NOT EXISTS).
--
-- Struktur aktual yang dipakai aplikasi CI3:
--   nosur            : id, no, kode, no_urut, huruf, nip, kj, tanggal, hal, tujuan, file
--   surmas           : id, kode, no_agenda, perihal, pengirim, no_surat,
--                      tgl_surat, tgl_diterima, keterangan, file, pengolah
--   user             : id, nip, id_jabatan, nama, pass, aktif
--   jabatan          : id, jabatan, subbag, kode
--   ref_klasifikasi  : id, kode, nama, uraian
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";

-- --------------------------------------------------------
-- Tabel `jabatan`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `jabatan` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `jabatan` varchar(50) NOT NULL,
  `subbag` varchar(15) NOT NULL,
  `kode` varchar(10) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Tabel `ref_klasifikasi`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ref_klasifikasi` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `kode` varchar(50) NOT NULL,
  `nama` varchar(250) NOT NULL,
  `uraian` mediumtext NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Tabel `surmas` (Surat Masuk)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `surmas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `kode` varchar(50) NOT NULL,
  `no_agenda` varchar(60) NOT NULL DEFAULT '-',
  `perihal` varchar(1000) NOT NULL,
  `pengirim` varchar(250) NOT NULL,
  `no_surat` varchar(100) NOT NULL,
  `tgl_surat` date NOT NULL,
  `tgl_diterima` date NOT NULL,
  `keterangan` varchar(200) NOT NULL,
  `file` varchar(2000) NOT NULL,
  `pengolah` varchar(30) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Tabel `nosur` (Surat Keluar)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `nosur` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `no` varchar(50) NOT NULL,
  `kode` varchar(3) NOT NULL,
  `no_urut` int(11) NOT NULL,
  `huruf` varchar(2) DEFAULT NULL,
  `nip` varchar(20) NOT NULL,
  `kj` varchar(10) NOT NULL,
  `tanggal` date NOT NULL,
  `hal` varchar(2000) NOT NULL,
  `tujuan` varchar(100) NOT NULL,
  `file` varchar(50) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Tabel `user`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nip` varchar(20) NOT NULL,
  `id_jabatan` int(11) NOT NULL,
  `nama` varchar(50) NOT NULL,
  `pass` varchar(100) NOT NULL,
  `aktif` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
