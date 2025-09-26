-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Sep 26, 2025 at 02:46 PM
-- Server version: 8.0.30
-- PHP Version: 8.1.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `db_toko`
--

-- --------------------------------------------------------

--
-- Table structure for table `tb_alamat`
--

CREATE TABLE `tb_alamat` (
  `id` int NOT NULL,
  `id_user` int NOT NULL,
  `nama_penerima` varchar(100) NOT NULL,
  `telepon` varchar(20) NOT NULL,
  `alamat_lengkap` text NOT NULL,
  `utama` tinyint(1) DEFAULT '0',
  `provinsi_id` int DEFAULT NULL,
  `regency_id` int DEFAULT NULL,
  `district_id` int DEFAULT NULL,
  `kode_pos` varchar(10) DEFAULT NULL,
  `detail_lainnya` varchar(255) DEFAULT NULL,
  `jenis_alamat` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `tb_alamat`
--

INSERT INTO `tb_alamat` (`id`, `id_user`, `nama_penerima`, `telepon`, `alamat_lengkap`, `utama`, `provinsi_id`, `regency_id`, `district_id`, `kode_pos`, `detail_lainnya`, `jenis_alamat`) VALUES
(2, 4, 'Divo Endrul', '082219353230', 'Jalan Cimanuk', 1, 32, 3274, 327404, '45111', 'dekat rumah abu', 'Rumah'),
(3, 7, 'Divo Endrul', '08112173173', 'jalan simeuleum', 1, 13, 1371, 137101, '45111', 'dekat rumah abu', 'Rumah'),
(6, 14, 'Divo Endrul Pratama', '082219353230', 'Jalan Cimanuk', 1, 32, 3274, 327404, '45111', 'dekat rumah abu', 'Rumah'),
(7, 15, 'Divo Endrul Pratama', '082219353230', 'Jalan Cimanuk', 1, 32, 3274, 327404, '45111', 'dekat rumah abu', 'Rumah');

-- --------------------------------------------------------

--
-- Table structure for table `tb_detail`
--

CREATE TABLE `tb_detail` (
  `id_detail` int NOT NULL,
  `id_transaksi` int DEFAULT NULL,
  `id_produk` int DEFAULT NULL,
  `jumlah` int DEFAULT NULL,
  `harga` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `tb_detail`
--

INSERT INTO `tb_detail` (`id_detail`, `id_transaksi`, `id_produk`, `jumlah`, `harga`) VALUES
(31, 9, 30, 3, 20000),
(32, 10, 31, 2, 20000),
(33, 11, 32, 1, 25000),
(46, 19, 35, 1, 15000),
(47, 20, 35, 1, 15000),
(48, 21, 35, 1, 15000),
(49, 21, 33, 1, 15000),
(50, 22, 39, 1, 20000),
(51, 22, 33, 1, 15000),
(52, 23, 34, 1, 30000),
(53, 24, 39, 1, 20000),
(54, 25, 39, 1, 20000),
(55, 26, 39, 1, 20000),
(57, 28, 39, 1, 20000);

--
-- Triggers `tb_detail`
--
DELIMITER $$
CREATE TRIGGER `after_insert_detail` AFTER INSERT ON `tb_detail` FOR EACH ROW BEGIN
    UPDATE tb_produk
    SET stok = stok - NEW.jumlah
    WHERE id = NEW.id_produk;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `tb_districts`
--

CREATE TABLE `tb_districts` (
  `id` int NOT NULL,
  `regency_id` int NOT NULL,
  `name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `tb_districts`
--

INSERT INTO `tb_districts` (`id`, `regency_id`, `name`) VALUES
(137101, 1371, 'BUNGUS TELUK KABUNG'),
(137102, 1371, 'LUBUK KILANGAN'),
(137103, 1371, 'PAUH'),
(137104, 1371, 'KOTO TANGAH'),
(137105, 1371, 'LUBUK BEGALUNG'),
(137106, 1371, 'PADANG SELATAN'),
(137107, 1371, 'PADANG TIMUR'),
(137108, 1371, 'PADANG BARAT'),
(137109, 1371, 'PADANG UTARA'),
(137110, 1371, 'NANGGALO'),
(137111, 1371, 'KURANJI'),
(327401, 3274, 'HARJAMUKTI'),
(327402, 3274, 'LEMAHWUNGKUK'),
(327403, 3274, 'PEKALIPAN'),
(327404, 3274, 'KESAMBI'),
(327405, 3274, 'KEJASAN');

-- --------------------------------------------------------

--
-- Table structure for table `tb_kategori`
--

CREATE TABLE `tb_kategori` (
  `id_kategori` int NOT NULL,
  `nama_kategori` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `tb_kategori`
--

INSERT INTO `tb_kategori` (`id_kategori`, `nama_kategori`) VALUES
(1, 'Cemilan'),
(2, 'Makanan Basah'),
(3, 'Kerajinan Tangan');

-- --------------------------------------------------------

--
-- Table structure for table `tb_pesan`
--

CREATE TABLE `tb_pesan` (
  `id` int NOT NULL,
  `nama` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `pesan` text NOT NULL,
  `tanggal` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `tb_pesan`
--

INSERT INTO `tb_pesan` (`id`, `nama`, `email`, `pesan`, `tanggal`) VALUES
(1, 'Divo Pratama', 'dipo@gmail.com', 'fdsfsf', '2025-09-23 00:12:48');

-- --------------------------------------------------------

--
-- Table structure for table `tb_produk`
--

CREATE TABLE `tb_produk` (
  `id` int NOT NULL,
  `nama` varchar(100) DEFAULT NULL,
  `harga` int DEFAULT NULL,
  `stok` int DEFAULT NULL,
  `deskripsi` text,
  `foto` varchar(255) DEFAULT NULL,
  `id_kategori` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `tb_produk`
--

INSERT INTO `tb_produk` (`id`, `nama`, `harga`, `stok`, `deskripsi`, `foto`, `id_kategori`) VALUES
(30, 'Keripik Sanjai', 20000, 1000, '.', '1757641159_3f9296026d.png', 1),
(31, 'Dendeng Balado', 20000, 999, 'Lezattt', '1757751914_ab4e265e55.png', 2),
(32, 'Rendang', 25000, 997, 'Mantappp', '1757751944_b76e1d55f1.png', 2),
(33, 'Sate Padang', 15000, 996, 'mantapp', '1757940751_96cb9b94e3.png', 2),
(34, 'Nasi Padang', 30000, 995, 'Isinya Paket lengkap', '1758119769_d0b395f48e.png', 2),
(39, 'Ayam Pop', 20000, 994, '', '1758119625_a4b73ee648.png', 2),
(43, 'Miniatur', 30000, 200, 'Rapih Dan Kokoh', '1758254239_9314b47a45.png', 3);

-- --------------------------------------------------------

--
-- Table structure for table `tb_provinces`
--

CREATE TABLE `tb_provinces` (
  `id` int NOT NULL,
  `name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `tb_provinces`
--

INSERT INTO `tb_provinces` (`id`, `name`) VALUES
(11, 'ACEH'),
(12, 'SUMATERA UTARA'),
(13, 'SUMATERA BARAT'),
(14, 'RIAU'),
(15, 'JAMBI'),
(16, 'SUMATERA SELATAN'),
(17, 'BENGKULU'),
(18, 'LAMPUNG'),
(19, 'KEPULAUAN BANGKA BELITUNG'),
(21, 'KEPULAUAN RIAU'),
(31, 'DKI JAKARTA'),
(32, 'JAWA BARAT'),
(33, 'JAWA TENGAH'),
(34, 'DI YOGYAKARTA'),
(35, 'JAWA TIMUR'),
(36, 'BANTEN'),
(51, 'BALI'),
(52, 'NUSA TENGGARA BARAT'),
(53, 'NUSA TENGGARA TIMUR'),
(61, 'KALIMANTAN BARAT'),
(62, 'KALIMANTAN TENGAH'),
(63, 'KALIMANTAN SELATAN'),
(64, 'KALIMANTAN TIMUR'),
(65, 'KALIMANTAN UTARA'),
(71, 'SULAWESI UTARA'),
(72, 'SULAWESI TENGAH'),
(73, 'SULAWESI SELATAN'),
(74, 'SULAWESI TENGGARA'),
(75, 'GORONTALO'),
(76, 'SULAWESI BARAT'),
(81, 'MALUKU'),
(82, 'MALUKU UTARA'),
(91, 'PAPUA BARAT'),
(94, 'PAPUA');

-- --------------------------------------------------------

--
-- Table structure for table `tb_regencies`
--

CREATE TABLE `tb_regencies` (
  `id` int NOT NULL,
  `province_id` int NOT NULL,
  `name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `tb_regencies`
--

INSERT INTO `tb_regencies` (`id`, `province_id`, `name`) VALUES
(1301, 13, 'KABUPATEN KEPULAUAN MENTAWAI'),
(1302, 13, 'KABUPATEN PESISIR SELATAN'),
(1303, 13, 'KABUPATEN SOLOK'),
(1304, 13, 'KABUPATEN SIJUNJUNG'),
(1305, 13, 'KABUPATEN TANAH DATAR'),
(1306, 13, 'KABUPATEN PADANG PARIAMAN'),
(1307, 13, 'KABUPATEN AGAM'),
(1308, 13, 'KABUPATEN LIMA PULUH KOTA'),
(1309, 13, 'KABUPATEN PASAMAN'),
(1310, 13, 'KABUPATEN SOLOK SELATAN'),
(1311, 13, 'KABUPATEN DHARMASRAYA'),
(1312, 13, 'KABUPATEN PASAMAN BARAT'),
(1371, 13, 'KOTA PADANG'),
(1372, 13, 'KOTA SOLOK'),
(1373, 13, 'KOTA SAWAHLUNTO'),
(1374, 13, 'KOTA PADANG PANJANG'),
(1375, 13, 'KOTA BUKITTINGGI'),
(1376, 13, 'KOTA PAYAKUMBUH'),
(1377, 13, 'KOTA PARIAMAN'),
(3201, 32, 'KABUPATEN BOGOR'),
(3202, 32, 'KABUPATEN SUKABUMI'),
(3203, 32, 'KABUPATEN CIANJUR'),
(3204, 32, 'KABUPATEN BANDUNG'),
(3205, 32, 'KABUPATEN GARUT'),
(3206, 32, 'KABUPATEN TASIKMALAYA'),
(3207, 32, 'KABUPATEN CIAMIS'),
(3208, 32, 'KABUPATEN KUNINGAN'),
(3209, 32, 'KABUPATEN CIREBON'),
(3210, 32, 'KABUPATEN MAJALENGKA'),
(3211, 32, 'KABUPATEN SUMEDANG'),
(3212, 32, 'KABUPATEN INDRAMAYU'),
(3213, 32, 'KABUPATEN SUBANG'),
(3214, 32, 'KABUPATEN PURWAKARTA'),
(3215, 32, 'KABUPATEN KARAWANG'),
(3216, 32, 'KABUPATEN BEKASI'),
(3217, 32, 'KABUPATEN BANDUNG BARAT'),
(3218, 32, 'KABUPATEN PANGANDARAN'),
(3271, 32, 'KOTA BOGOR'),
(3272, 32, 'KOTA SUKABUMI'),
(3273, 32, 'KOTA BANDUNG'),
(3274, 32, 'KOTA CIREBON'),
(3275, 32, 'KOTA BEKASI'),
(3276, 32, 'KOTA DEPOK'),
(3277, 32, 'KOTA CIMAHI'),
(3278, 32, 'KOTA TASIKMALAYA'),
(3279, 32, 'KOTA BANJAR');

-- --------------------------------------------------------

--
-- Table structure for table `tb_transaksi`
--

CREATE TABLE `tb_transaksi` (
  `id_transaksi` int NOT NULL,
  `id_user` int NOT NULL,
  `id_pelanggan` int NOT NULL,
  `nama_penerima` varchar(100) NOT NULL,
  `nohp` varchar(20) NOT NULL,
  `alamat` text,
  `tanggal` datetime NOT NULL,
  `metode_pembayaran` varchar(100) NOT NULL,
  `metode_pengiriman` varchar(100) NOT NULL,
  `catatan` text,
  `total` int NOT NULL,
  `status_pengiriman` enum('dibuat','diproses','dikirim','diterima') NOT NULL DEFAULT 'dibuat',
  `status` enum('pending','selesai') DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `tb_transaksi`
--

INSERT INTO `tb_transaksi` (`id_transaksi`, `id_user`, `id_pelanggan`, `nama_penerima`, `nohp`, `alamat`, `tanggal`, `metode_pembayaran`, `metode_pengiriman`, `catatan`, `total`, `status_pengiriman`, `status`) VALUES
(19, 14, 14, 'Divo Endrul Pratama', '082219353230', 'Jalan Cimanuk, KESAMBI, KOTA CIREBON, JAWA BARAT, ID 45111', '2025-09-17 21:08:18', 'COD - Cek Dulu', 'Hemat Kargo', '', 20500, 'diterima', 'pending'),
(20, 14, 14, 'Divo Endrul Pratama', '082219353230', 'Jalan Cimanuk, KESAMBI, KOTA CIREBON, JAWA BARAT, ID 45111', '2025-09-17 21:08:43', 'COD - Cek Dulu', 'Hemat Kargo', '', 20500, 'diterima', 'pending'),
(21, 14, 14, 'Divo Endrul Pratama', '082219353230', 'Jalan Cimanuk, KESAMBI, KOTA CIREBON, JAWA BARAT, ID 45111', '2025-09-17 21:10:47', 'COD - Cek Dulu', 'Hemat Kargo', '', 35500, 'diterima', 'pending'),
(22, 14, 14, 'Divo Endrul Pratama', '082219353230', 'Jalan Cimanuk, KESAMBI, KOTA CIREBON, JAWA BARAT, ID 45111', '2025-09-17 21:21:20', 'COD - Cek Dulu', 'Hemat Kargo', '', 40500, 'diterima', 'pending'),
(23, 14, 14, 'Divo Endrul Pratama', '082219353230', 'Jalan Cimanuk, KESAMBI, KOTA CIREBON, JAWA BARAT, ID 45111', '2025-09-17 21:23:14', 'COD - Cek Dulu', 'Hemat Kargo', '', 35500, 'diterima', 'pending'),
(24, 14, 14, 'Divo Endrul Pratama', '082219353230', 'Jalan Cimanuk, KESAMBI, KOTA CIREBON, JAWA BARAT, ID 45111', '2025-09-17 21:53:05', 'COD - Cek Dulu', 'Reguler', '', 28500, 'diterima', 'pending'),
(25, 14, 14, 'Divo Endrul Pratama', '082219353230', 'Jalan Cimanuk, KESAMBI, KOTA CIREBON, JAWA BARAT, ID 45111', '2025-09-19 07:45:39', 'COD - Cek Dulu', 'Hemat Kargo', '', 25500, 'diterima', 'pending'),
(26, 14, 14, 'Divo Endrul Pratama', '082219353230', 'Jalan Cimanuk, KESAMBI, KOTA CIREBON, JAWA BARAT, ID 45111', '2025-09-19 08:14:32', 'COD - Cek Dulu', 'Hemat Kargo', '', 25500, 'diproses', 'pending'),
(28, 15, 15, 'Divo Endrul Pratama', '082219353230', 'Jalan Cimanuk, KESAMBI, KOTA CIREBON, JAWA BARAT, ID 45111', '2025-09-19 11:02:04', 'COD - Cek Dulu', 'Hemat Kargo', '', 25500, 'diterima', 'pending');

-- --------------------------------------------------------

--
-- Table structure for table `tb_user`
--

CREATE TABLE `tb_user` (
  `id` int NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nama` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `alamat` varchar(255) DEFAULT NULL,
  `telepon` varchar(20) DEFAULT NULL,
  `jenis_kelamin` varchar(20) DEFAULT NULL,
  `tgl_lahir` date DEFAULT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `role` enum('admin','pelanggan') NOT NULL DEFAULT 'pelanggan'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `tb_user`
--

INSERT INTO `tb_user` (`id`, `username`, `password`, `nama`, `email`, `alamat`, `telepon`, `jenis_kelamin`, `tgl_lahir`, `foto`, `role`) VALUES
(14, 'divoen', '$2y$10$S5VZ8wnrKnpRBeZ8B1REy.SOP.i4TjL54lWnHA4hV4MIOFIaEHBQG', 'Divo Endrul Pratama', 'divoendrulpratama@gmail.com', NULL, '082219353230', 'Laki-laki', '2009-08-05', '1758201794_b0bb9bc32d03.png', 'pelanggan'),
(15, 'halo', '$2y$10$FD9fvIVxMGslfinp6S.w1uWpuxVRCK2.iy1PZ8r6jnc6rPMGUy25q', 'Divo Pratama', 'dipo@gmail.com', NULL, '082219353230', 'Laki-laki', '2009-06-17', '1758254437_29afb569d4a1.png', 'pelanggan');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `tb_alamat`
--
ALTER TABLE `tb_alamat`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_user` (`id_user`);

--
-- Indexes for table `tb_detail`
--
ALTER TABLE `tb_detail`
  ADD PRIMARY KEY (`id_detail`),
  ADD KEY `id_transaksi` (`id_transaksi`),
  ADD KEY `id_produk` (`id_produk`);

--
-- Indexes for table `tb_districts`
--
ALTER TABLE `tb_districts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `tb_districts_ibfk_1` (`regency_id`);

--
-- Indexes for table `tb_kategori`
--
ALTER TABLE `tb_kategori`
  ADD PRIMARY KEY (`id_kategori`);

--
-- Indexes for table `tb_pesan`
--
ALTER TABLE `tb_pesan`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tb_produk`
--
ALTER TABLE `tb_produk`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_produk_kategori` (`id_kategori`);

--
-- Indexes for table `tb_provinces`
--
ALTER TABLE `tb_provinces`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tb_regencies`
--
ALTER TABLE `tb_regencies`
  ADD PRIMARY KEY (`id`),
  ADD KEY `tb_regencies_ibfk_1` (`province_id`);

--
-- Indexes for table `tb_transaksi`
--
ALTER TABLE `tb_transaksi`
  ADD PRIMARY KEY (`id_transaksi`),
  ADD KEY `id_pelanggan` (`id_pelanggan`);

--
-- Indexes for table `tb_user`
--
ALTER TABLE `tb_user`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `tb_alamat`
--
ALTER TABLE `tb_alamat`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `tb_detail`
--
ALTER TABLE `tb_detail`
  MODIFY `id_detail` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=58;

--
-- AUTO_INCREMENT for table `tb_districts`
--
ALTER TABLE `tb_districts`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=327406;

--
-- AUTO_INCREMENT for table `tb_kategori`
--
ALTER TABLE `tb_kategori`
  MODIFY `id_kategori` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `tb_pesan`
--
ALTER TABLE `tb_pesan`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tb_produk`
--
ALTER TABLE `tb_produk`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- AUTO_INCREMENT for table `tb_provinces`
--
ALTER TABLE `tb_provinces`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=95;

--
-- AUTO_INCREMENT for table `tb_regencies`
--
ALTER TABLE `tb_regencies`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3280;

--
-- AUTO_INCREMENT for table `tb_transaksi`
--
ALTER TABLE `tb_transaksi`
  MODIFY `id_transaksi` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `tb_user`
--
ALTER TABLE `tb_user`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `tb_districts`
--
ALTER TABLE `tb_districts`
  ADD CONSTRAINT `tb_districts_ibfk_1` FOREIGN KEY (`regency_id`) REFERENCES `tb_regencies` (`id`);

--
-- Constraints for table `tb_produk`
--
ALTER TABLE `tb_produk`
  ADD CONSTRAINT `fk_produk_kategori` FOREIGN KEY (`id_kategori`) REFERENCES `tb_kategori` (`id_kategori`);

--
-- Constraints for table `tb_regencies`
--
ALTER TABLE `tb_regencies`
  ADD CONSTRAINT `tb_regencies_ibfk_1` FOREIGN KEY (`province_id`) REFERENCES `tb_provinces` (`id`);

--
-- Constraints for table `tb_transaksi`
--
ALTER TABLE `tb_transaksi`
  ADD CONSTRAINT `tb_transaksi_ibfk_1` FOREIGN KEY (`id_pelanggan`) REFERENCES `tb_user` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
