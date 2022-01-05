-- phpMyAdmin SQL Dump
-- version 5.1.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 05, 2022 at 12:58 AM
-- Server version: 10.4.21-MariaDB
-- PHP Version: 8.0.11

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
  `file` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `id` int(5) NOT NULL,
  `nip` varchar(20) NOT NULL,
  `nama` varchar(50) NOT NULL,
  `pass` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`id`, `nip`, `nama`, `pass`) VALUES
(1, 'ketua', 'Rasyid Muzhar, S.Ag., M.H.', 'e10adc3949ba59abbe56e057f20f883e'),
(2, 'wakil', 'Sriyani HN, S.Ag., MH', 'e10adc3949ba59abbe56e057f20f883e'),
(3, 'hakim1', 'Drs. Mansyur', 'e10adc3949ba59abbe56e057f20f883e'),
(4, 'hakim2', 'Fauziah Burhan, S.H.I.', 'e10adc3949ba59abbe56e057f20f883e'),
(5, 'hakim3', '', 'e10adc3949ba59abbe56e057f20f883e'),
(6, 'hakim4', '', 'e10adc3949ba59abbe56e057f20f883e'),
(7, 'hakim5', '', 'e10adc3949ba59abbe56e057f20f883e'),
(8, 'panitera', 'Sahbudin Kesi, S.Ag., M.H.', 'e10adc3949ba59abbe56e057f20f883e'),
(9, 'panmudhukum', 'Eva Farihat Fauziyah, S.Ag', 'e10adc3949ba59abbe56e057f20f883e'),
(10, 'panmudpermohonan', 'Maryam Abubakar, S.H.', 'e10adc3949ba59abbe56e057f20f883e'),
(11, 'panmudgugatan', 'Fatimah Mahben, S.Ag., M.H.', 'e10adc3949ba59abbe56e057f20f883e'),
(12, 'sekretaris', 'Rofian, S.H.I., M.H.', 'e10adc3949ba59abbe56e057f20f883e'),
(13, 'kasubumum', 'Nuraini Mahmud, S.E.', 'e10adc3949ba59abbe56e057f20f883e'),
(14, 'kasubpeg', 'Khalil Wazir Bin Idris, S.Kom.', 'e10adc3949ba59abbe56e057f20f883e'),
(15, 'kasubptip', 'Aisyah, S.Kom., M.H.', 'e10adc3949ba59abbe56e057f20f883e'),
(16, 'jurusita1', 'Adhi Danial Hamid', 'e10adc3949ba59abbe56e057f20f883e'),
(17, 'jurusita2', 'Wahyu Ardiansyah', 'e10adc3949ba59abbe56e057f20f883e'),
(18, 'verkeu', 'Luqmanul Khakim, S.E.', 'e10adc3949ba59abbe56e057f20f883e'),
(19, 'prakom', 'Dhimas Radhito, S.Kom.', 'e10adc3949ba59abbe56e057f20f883e'),
(20, 'admin', 'Administrator', 'e10adc3949ba59abbe56e057f20f883e');

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
  MODIFY `id` int(5) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `id` int(5) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
