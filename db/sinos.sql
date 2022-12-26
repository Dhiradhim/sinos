-- phpMyAdmin SQL Dump
-- version 5.0.4
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 26, 2022 at 08:58 AM
-- Server version: 10.4.17-MariaDB
-- PHP Version: 8.0.2

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `sinos`
--

-- --------------------------------------------------------

--
-- Table structure for table `nosur`
--

CREATE TABLE `nosur` (
  `id` int(5) NOT NULL,
  `no` varchar(40) NOT NULL,
  `no_urut` varchar(4) NOT NULL,
  `nip` varchar(20) NOT NULL,
  `tanggal` date NOT NULL,
  `hal` varchar(100) NOT NULL,
  `file` varchar(50) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `nosur`
--

INSERT INTO `nosur` (`id`, `no`, `no_urut`, `nip`, `tanggal`, `hal`, `file`) VALUES
(1, 'W23-A1/1/OT.02.3/XII/2022', '1', 'prakom', '2022-12-12', 'Permohonan Konfirmasi ke KPPN', '1'),
(2, 'W23-A1/2/KU.03.4/XII/2022', '2', 'panmudhukum', '2022-12-12', 'Perihal 4', 'file/2_file.pdf'),
(3, 'W23-A1/3/KS.05/XII/2022', '3', 'panmudhukum', '2022-12-13', 'Perihal 3', '1');

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `id` int(5) NOT NULL,
  `nip` varchar(20) NOT NULL,
  `nama` varchar(50) NOT NULL,
  `pass` varchar(100) NOT NULL,
  `aktif` int(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`id`, `nip`, `nama`, `pass`, `aktif`) VALUES
(1, 'ketua', 'Mhd. Harmaini, S.Ag., S.H.', 'e10adc3949ba59abbe56e057f20f883e', 0),
(2, 'wakil', '', 'e10adc3949ba59abbe56e057f20f883e', 1),
(3, 'hakim1', 'Dra. Hj. Medang, M.H.', 'e10adc3949ba59abbe56e057f20f883e', 0),
(4, 'hakim2', 'Moh. Rivai, S.H.I., M.H.', 'e10adc3949ba59abbe56e057f20f883e', 0),
(5, 'hakim3', '', 'e10adc3949ba59abbe56e057f20f883e', 1),
(6, 'hakim4', '', 'e10adc3949ba59abbe56e057f20f883e', 1),
(7, 'hakim5', '', 'e10adc3949ba59abbe56e057f20f883e', 1),
(8, 'panitera', 'Sahbudin Kesi, S.Ag., M.H.', 'e10adc3949ba59abbe56e057f20f883e', 0),
(9, 'panmudhukum', 'Eva Farihat Fauziyah, S.Ag.', 'e10adc3949ba59abbe56e057f20f883e', 0),
(10, 'panmudpermohonan', 'Maryam Abubakar, S.H.', 'e10adc3949ba59abbe56e057f20f883e', 0),
(11, 'panmudgugatan', 'Fatimah Mahben, S.Ag., M.H.', 'e10adc3949ba59abbe56e057f20f883e', 0),
(12, 'sekretaris', 'Rofian, S.H.I., M.H.', 'e10adc3949ba59abbe56e057f20f883e', 0),
(13, 'kasubumum', 'Nuraini Mahmud, S.E.', 'e10adc3949ba59abbe56e057f20f883e', 0),
(14, 'kasubpeg', 'Khalil Wazir Bin Idris, S.Kom.', 'e10adc3949ba59abbe56e057f20f883e', 0),
(15, 'kasubptip', 'Aisyah, S.Kom., M.H.', 'e10adc3949ba59abbe56e057f20f883e', 0),
(16, 'jurusita1', 'Adhi Danial Hamid', 'e10adc3949ba59abbe56e057f20f883e', 0),
(17, 'jurusita2', 'Wahyu Ardiansyah, S.H.', 'e10adc3949ba59abbe56e057f20f883e', 0),
(18, 'staff_keu', 'Luqmanul Khakim, S.E.', 'e10adc3949ba59abbe56e057f20f883e', 0),
(19, 'prakom', 'Dhimas Radhito, S.Kom.', 'e10adc3949ba59abbe56e057f20f883e', 0),
(20, 'admin', 'Administrator', 'e10adc3949ba59abbe56e057f20f883e', 1),
(21, 'app1', 'Aditya Yudha Prawira, S.H.', 'e10adc3949ba59abbe56e057f20f883e', 0),
(22, 'app2', 'Rafli Tirtabuana, S.H.', 'e10adc3949ba59abbe56e057f20f883e', 0),
(23, 'apep', 'Muhammad Emir Herbawono, S.I.A.', 'e10adc3949ba59abbe56e057f20f883e', 0),
(24, 'pbmn', 'Devi Lovenia Setyoharti, A.Md.Ak', 'e10adc3949ba59abbe56e057f20f883e', 0),
(25, 'pp1', 'Ermin Tanaya Kencanawati, A.Md.A.B', 'e10adc3949ba59abbe56e057f20f883e', 0),
(26, 'pp2', 'Shofiya Hasanah, A.Md.', 'e10adc3949ba59abbe56e057f20f883e', 0),
(27, 'paniterapengganti1', 'Abdullah Umar, S.H.I.', 'e10adc3949ba59abbe56e057f20f883e', 0),
(28, 'paniterapengganti2', 'Nur Amalia Mandasari, S.E.I.', 'e10adc3949ba59abbe56e057f20f883e', 0);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `nosur`
--
ALTER TABLE `nosur`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `nosur`
--
ALTER TABLE `nosur`
  MODIFY `id` int(5) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `id` int(5) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
