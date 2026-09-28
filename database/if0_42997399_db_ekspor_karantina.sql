-- phpMyAdmin SQL Dump
-- version 4.9.0.1
-- https://www.phpmyadmin.net/
--
-- Host: sql209.infinityfree.com
-- Generation Time: Sep 27, 2026 at 09:26 PM
-- Server version: 11.4.13-MariaDB
-- PHP Version: 7.2.22

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `if0_42997399_db_ekspor_karantina`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_users`
--

CREATE TABLE `admin_users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `role` enum('admin','user') NOT NULL DEFAULT 'admin',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admin_users`
--

INSERT INTO `admin_users` (`id`, `username`, `password`, `nama`, `role`, `is_active`, `created_at`) VALUES
(1, 'admin', '$2y$12$CpBfCButX3iiQLXzh991l.9SXXmVeQtnvsQUeL0PKYu6vPA2qKbdO', 'Admin', 'admin', 1, '2026-09-23 14:09:42');

-- --------------------------------------------------------

--
-- Table structure for table `ekspor_komoditas`
--

CREATE TABLE `ekspor_komoditas` (
  `id` int(11) NOT NULL,
  `jenis_kegiatan` enum('ekspor','impor','domestik_keluar','domestik_masuk') NOT NULL DEFAULT 'ekspor',
  `kategori` enum('hewan','ikan','tumbuhan') NOT NULL,
  `nama_komoditas` varchar(255) NOT NULL,
  `nama_komoditas_lokal` varchar(255) DEFAULT NULL,
  `frekuensi` int(11) DEFAULT 0,
  `volume` decimal(18,2) DEFAULT 0.00,
  `satuan` varchar(50) DEFAULT 'Kilogram',
  `nilai_ekspor` decimal(20,2) DEFAULT 0.00,
  `negara_tujuan` text DEFAULT NULL,
  `periode` varchar(50) NOT NULL,
  `tanggal_mulai` date DEFAULT NULL,
  `tanggal_selesai` date DEFAULT NULL,
  `is_published` tinyint(1) DEFAULT 0,
  `uploaded_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `ekspor_komoditas`
--

INSERT INTO `ekspor_komoditas` (`id`, `jenis_kegiatan`, `kategori`, `nama_komoditas`, `nama_komoditas_lokal`, `frekuensi`, `volume`, `satuan`, `nilai_ekspor`, `negara_tujuan`, `periode`, `tanggal_mulai`, `tanggal_selesai`, `is_published`, `uploaded_at`) VALUES
(25, 'ekspor', 'tumbuhan', 'DAMAR BATU', NULL, 66, '1428000.00', 'Kilogram', '16228447706.00', 'India, Pakistan, Thailand, Banglades', '2026-01_2026-07', '2026-01-01', '2026-07-31', 1, '2026-09-24 18:12:28'),
(26, 'ekspor', 'tumbuhan', 'DAUN SENA', NULL, 42, '104867.55', 'Kilogram', '9174638153.00', 'Jepang', '2026-01_2026-07', '2026-01-01', '2026-07-31', 1, '2026-09-24 18:12:28'),
(27, 'ekspor', 'tumbuhan', 'KARET LEMPENGAN', NULL, 21, '2217600.00', 'Kilogram', '89843607230.00', 'China, Uni Emirad Arab, Eropa', '2026-01_2026-07', '2026-01-01', '2026-07-31', 1, '2026-09-24 18:12:28'),
(28, 'ekspor', 'tumbuhan', 'KAYU LAPIS (plywood)', NULL, 79, '42939.49', 'Meter Kubik', '55531772698.00', 'Phillipina, Thailand, Malaysia, Jerman, Australia, India.', '2026-01_2026-07', '2026-01-01', '2026-07-31', 1, '2026-09-24 18:12:28'),
(29, 'ekspor', 'tumbuhan', 'KAYU OLAHAN (moulding)', NULL, 3, '152.95', 'Meter Kubik', '16257430.00', 'Jerman', '2026-01_2026-07', '2026-01-01', '2026-07-31', 1, '2026-09-24 18:12:28'),
(30, 'ekspor', 'tumbuhan', 'KAYU VENEER', NULL, 17, '1235.49', 'Meter Kubik', '1299868009250.00', 'India.', '2026-01_2026-07', '2026-01-01', '2026-07-31', 1, '2026-09-24 18:12:28'),
(31, 'ekspor', 'tumbuhan', 'PALM KERNEL EXPELLER', NULL, 20, '22290955.00', 'Kilogram', '47656939697.00', 'Vietnam, China', '2026-01_2026-07', '2026-01-01', '2026-07-31', 1, '2026-09-24 18:12:28'),
(32, 'ekspor', 'tumbuhan', 'PALM KERNEL MEAL', NULL, 5, '11521789.00', 'Kilogram', '18189477102.00', 'Vietnam.', '2026-01_2026-07', '2026-01-01', '2026-07-31', 1, '2026-09-24 18:12:28'),
(33, 'ekspor', 'tumbuhan', 'LAMPIT (RATTAN MAT WEBBING)', NULL, 2, '3050.00', 'Kilogram', '716477370.00', 'Taiwan', '2026-01_2026-07', '2026-01-01', '2026-07-31', 1, '2026-09-24 18:12:28'),
(34, 'ekspor', 'tumbuhan', 'RBD PALM KERNEL OIL', NULL, 13, '136530460.00', 'Kilogram', '2849970520890.00', 'Singapura, China.', '2026-01_2026-07', '2026-01-01', '2026-07-31', 1, '2026-09-24 18:12:28'),
(35, 'ekspor', 'tumbuhan', 'RBD PALM STEARIN', NULL, 21, '63998464.00', 'Kilogram', '1041331690410.00', 'Singapura, China.', '2026-01_2026-07', '2026-01-01', '2026-07-31', 1, '2026-09-24 18:12:28'),
(36, 'ekspor', 'tumbuhan', 'SAWDUST BRIQUETTE CHARCOAL', NULL, 1, '22930.00', 'Kilogram', '137400000.00', 'Jepang', '2026-01_2026-07', '2026-01-01', '2026-07-31', 1, '2026-09-24 18:12:28'),
(37, 'ekspor', 'tumbuhan', 'DAMAR BATU', NULL, 79, '1703550.00', 'Kilogram', '19023935564.00', 'India, Pakistan, Thailand, Banglades', '2026-01_2026-09', '2026-01-01', '2026-09-30', 1, '2026-09-24 18:12:42'),
(38, 'ekspor', 'tumbuhan', 'DAUN SENA', NULL, 47, '121078.00', 'Kilogram', '10559736419.00', 'Jepang', '2026-01_2026-09', '2026-01-01', '2026-09-30', 1, '2026-09-24 18:12:42'),
(39, 'ekspor', 'tumbuhan', 'KARET LEMPENGAN', NULL, 21, '2217600.00', 'Kilogram', '89843607230.00', 'China, Uni Emirad Arab, Eropa', '2026-01_2026-09', '2026-01-01', '2026-09-30', 1, '2026-09-24 18:12:42'),
(40, 'ekspor', 'tumbuhan', 'KAYU LAPIS (plywood)', NULL, 92, '250450.00', 'Meter Kubik', '63629882371.00', 'Phillipina, Thailand, Malaysia, Jerman, Australia, India.', '2026-01_2026-09', '2026-01-01', '2026-09-30', 1, '2026-09-24 18:12:42'),
(41, 'ekspor', 'tumbuhan', 'KAYU OLAHAN (moulding)', NULL, 3, '152.95', 'Meter Kubik', '16257430.00', 'Jerman', '2026-01_2026-09', '2026-01-01', '2026-09-30', 1, '2026-09-24 18:12:42'),
(42, 'ekspor', 'tumbuhan', 'KAYU VENEER', NULL, 18, '1255.00', 'Meter Kubik', '1300294738410.00', 'India.', '2026-01_2026-09', '2026-01-01', '2026-09-30', 1, '2026-09-24 18:12:42'),
(43, 'ekspor', 'tumbuhan', 'PALM KERNEL EXPELLER', NULL, 27, '32658275.00', 'Kilogram', '75005640498.00', 'Vietnam, China', '2026-01_2026-09', '2026-01-01', '2026-09-30', 1, '2026-09-24 18:12:42'),
(44, 'ekspor', 'tumbuhan', 'PALM KERNEL MEAL', NULL, 5, '11521789.00', 'Kilogram', '18189477102.00', 'Vietnam.', '2026-01_2026-09', '2026-01-01', '2026-09-30', 1, '2026-09-24 18:12:42'),
(45, 'ekspor', 'tumbuhan', 'LAMPIT (RATTAN MAT WEBBING)', NULL, 2, '3050.00', 'Kilogram', '716477370.00', 'Taiwan', '2026-01_2026-09', '2026-01-01', '2026-09-30', 1, '2026-09-24 18:12:42'),
(46, 'ekspor', 'tumbuhan', 'RBD PALM KERNEL OIL', NULL, 20, '146869040.00', 'Kilogram', '3233713339690.00', 'Singapura, China.', '2026-01_2026-09', '2026-01-01', '2026-09-30', 1, '2026-09-24 18:12:42'),
(47, 'ekspor', 'tumbuhan', 'RBD PALM STEARIN', NULL, 21, '63998464.00', 'Kilogram', '1041331690410.00', 'Singapura, China.', '2026-01_2026-09', '2026-01-01', '2026-09-30', 1, '2026-09-24 18:12:42'),
(48, 'ekspor', 'tumbuhan', 'SAWDUST BRIQUETTE CHARCOAL', NULL, 1, '22930.00', 'Kilogram', '137400000.00', 'Jepang', '2026-01_2026-09', '2026-01-01', '2026-09-30', 1, '2026-09-24 18:12:42'),
(49, 'ekspor', 'hewan', 'Sarang Burung Walet', NULL, 15, '505.50', 'Kilogram', '8354207200.00', 'Hongkong', '2026-01_2026-09', '2026-01-01', '2026-09-30', 1, '2026-09-24 18:47:27');

-- --------------------------------------------------------

--
-- Table structure for table `ekspor_settings`
--

CREATE TABLE `ekspor_settings` (
  `id` int(11) NOT NULL,
  `jenis_kegiatan` enum('ekspor','impor','domestik_keluar','domestik_masuk') NOT NULL DEFAULT 'ekspor',
  `kategori` enum('hewan','ikan','tumbuhan') NOT NULL,
  `top_n` int(11) DEFAULT 5,
  `urutkan_berdasarkan` enum('nilai_ekspor','frekuensi') DEFAULT 'nilai_ekspor',
  `filter_mode` enum('tahun','bulan','hari','semua') DEFAULT 'tahun',
  `tanggal_awal` date DEFAULT NULL,
  `tanggal_akhir` date DEFAULT NULL,
  `tahun_awal` smallint(6) DEFAULT NULL,
  `tahun_akhir` smallint(6) DEFAULT NULL,
  `periode` varchar(50) DEFAULT NULL,
  `periode_label` varchar(100) DEFAULT NULL,
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `ekspor_settings`
--

INSERT INTO `ekspor_settings` (`id`, `jenis_kegiatan`, `kategori`, `top_n`, `urutkan_berdasarkan`, `filter_mode`, `tanggal_awal`, `tanggal_akhir`, `tahun_awal`, `tahun_akhir`, `periode`, `periode_label`, `updated_at`) VALUES
(1, 'ekspor', 'tumbuhan', 1, 'nilai_ekspor', 'bulan', '2026-01-01', '2026-12-31', 2026, 2026, '2026-01_2026-07', 'JANUARI - DESEMBER 2026', '2026-09-24 20:44:12'),
(2, 'ekspor', 'tumbuhan', 5, 'nilai_ekspor', 'bulan', '2026-01-01', '2026-12-31', 2026, 2026, '2026-01_2026-09', 'JANUARI - DESEMBER 2026', '2026-09-24 18:13:32'),
(3, 'ekspor', 'hewan', 5, 'nilai_ekspor', 'bulan', '2026-01-01', '2026-12-31', 2026, 2026, '2026-01_2026-09', 'JANUARI - DESEMBER 2026', '2026-09-24 18:51:06');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_users`
--
ALTER TABLE `admin_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `ekspor_komoditas`
--
ALTER TABLE `ekspor_komoditas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_kategori` (`kategori`),
  ADD KEY `idx_jenis_kegiatan` (`jenis_kegiatan`),
  ADD KEY `idx_jenis_kategori_pub` (`jenis_kegiatan`,`kategori`,`is_published`),
  ADD KEY `idx_is_published` (`is_published`),
  ADD KEY `idx_periode` (`periode`);

--
-- Indexes for table `ekspor_settings`
--
ALTER TABLE `ekspor_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_jenis_kategori_periode` (`jenis_kegiatan`,`kategori`,`periode`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_users`
--
ALTER TABLE `admin_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `ekspor_komoditas`
--
ALTER TABLE `ekspor_komoditas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;

--
-- AUTO_INCREMENT for table `ekspor_settings`
--
ALTER TABLE `ekspor_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
