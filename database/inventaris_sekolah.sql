-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 19, 2025 at 04:13 AM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `inventaris_sekolah`
--

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_laporan_peminjaman` (IN `p_tanggal_mulai` DATE, IN `p_tanggal_akhir` DATE, IN `p_status` VARCHAR(20))   BEGIN
    SELECT 
        p.kode_peminjaman,
        b.nama_barang,
        p.peminjam_nama,
        p.peminjam_kelas,
        p.jumlah_pinjam,
        p.tanggal_pinjam,
        p.tanggal_kembali_rencana,
        p.tanggal_kembali_aktual,
        p.status,
        u.nama_lengkap as petugas
    FROM peminjaman p
    JOIN barang b ON p.barang_id = b.id
    JOIN users u ON p.created_by = u.id
    WHERE p.tanggal_pinjam BETWEEN p_tanggal_mulai AND p_tanggal_akhir
    AND (p_status IS NULL OR p.status = p_status)
    ORDER BY p.tanggal_pinjam DESC;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_laporan_stok_barang` (IN `p_kategori_id` INT, IN `p_lokasi_id` INT, IN `p_kondisi` VARCHAR(20))   BEGIN
    SELECT 
        b.kode_barang,
        b.nama_barang,
        k.nama_kategori,
        l.nama_lokasi,
        b.jumlah_total,
        b.jumlah_tersedia,
        (b.jumlah_total - b.jumlah_tersedia) as jumlah_dipinjam,
        b.kondisi,
        b.tahun_pengadaan,
        b.harga_perolehan
    FROM barang b
    JOIN kategori k ON b.kategori_id = k.id
    JOIN lokasi l ON b.lokasi_id = l.id
    WHERE (p_kategori_id IS NULL OR b.kategori_id = p_kategori_id)
    AND (p_lokasi_id IS NULL OR b.lokasi_id = p_lokasi_id)
    AND (p_kondisi IS NULL OR b.kondisi = p_kondisi)
    ORDER BY b.nama_barang;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `barang`
--

CREATE TABLE `barang` (
  `id` int(11) NOT NULL,
  `nama_barang` varchar(200) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `kategori_id` int(11) NOT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `barang`
--

INSERT INTO `barang` (`id`, `nama_barang`, `deskripsi`, `kategori_id`, `foto`, `created_at`, `updated_at`) VALUES
(38, 'Meja Belajar', 'Meja belajar kelas', 1, '691d1f1710103.jpeg', '2025-11-19 01:36:23', '2025-11-19 01:36:23'),
(39, 'Pena', 'Pena baru nich', 3, '691d1f324813a.png', '2025-11-19 01:36:50', '2025-11-19 01:36:50');

-- --------------------------------------------------------

--
-- Table structure for table `kategori`
--

CREATE TABLE `kategori` (
  `id` int(11) NOT NULL,
  `nama_kategori` varchar(100) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `kategori`
--

INSERT INTO `kategori` (`id`, `nama_kategori`, `deskripsi`, `created_at`, `updated_at`) VALUES
(1, 'Meubel', 'Barang-barang meubel seperti meja, kursi, lemari', '2025-08-01 14:28:53', '2025-08-01 14:28:53'),
(2, 'Elektronik', 'Barang elektronik seperti komputer, proyektor, printer', '2025-08-01 14:28:53', '2025-08-01 14:28:53'),
(3, 'ATK', 'Alat Tulis Kantor', '2025-08-01 14:28:53', '2025-08-01 14:28:53'),
(4, 'Olahraga', 'Peralatan olahraga', '2025-08-01 14:28:53', '2025-08-01 14:28:53'),
(5, 'Laboratorium', 'Peralatan laboratorium', '2025-08-01 14:28:53', '2025-08-01 14:28:53'),
(6, 'Perpustakaan', 'Buku dan peralatan perpustakaan', '2025-08-01 14:28:53', '2025-08-01 14:28:53');

-- --------------------------------------------------------

--
-- Table structure for table `pengaturan`
--

CREATE TABLE `pengaturan` (
  `id` int(11) NOT NULL,
  `nama_pengaturan` varchar(100) NOT NULL,
  `nilai` text DEFAULT NULL,
  `deskripsi` text DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pengaturan`
--

INSERT INTO `pengaturan` (`id`, `nama_pengaturan`, `nilai`, `deskripsi`, `updated_at`) VALUES
(1, 'nama_sekolah', 'SMA Negeri 1 Contoh', 'Nama sekolah', '2025-08-01 14:28:53'),
(2, 'alamat_sekolah', 'Jl. Contoh No. 123, Kota Contoh', 'Alamat sekolah', '2025-08-01 14:28:53'),
(3, 'telepon_sekolah', '021-12345677', 'Nomor telepon sekolah', '2025-08-02 06:33:09'),
(4, 'email_sekolah', 'info@sman1contoh.sch.id', 'Email sekolah', '2025-08-01 14:28:53'),
(5, 'maksimal_peminjaman', '10', 'Maksimal hari peminjaman', '2025-08-02 08:17:02'),
(6, 'denda_terlambat', '1000', 'Denda per hari keterlambatan (Rp)', '2025-08-02 06:43:30');

-- --------------------------------------------------------

--
-- Table structure for table `transaksi`
--

CREATE TABLE `transaksi` (
  `id` int(11) NOT NULL,
  `barang_id` int(11) DEFAULT NULL,
  `deskripsi` longtext DEFAULT NULL,
  `kondisi` enum('baik','rusak_ringan','rusak_berat') DEFAULT 'baik',
  `jumlah` int(11) DEFAULT NULL,
  `harga_satuan` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total` decimal(15,2) DEFAULT 0.00,
  `tahun_pengadaan` year(4) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transaksi`
--

INSERT INTO `transaksi` (`id`, `barang_id`, `deskripsi`, `kondisi`, `jumlah`, `harga_satuan`, `total`, `tahun_pengadaan`, `created_at`, `updated_at`) VALUES
(1, 38, 'Meja belajar kelas 1', 'baik', 100, 3000.00, 300000.00, '2025', '2025-11-19 02:12:38', '2025-11-19 02:12:38'),
(2, 39, 'Pena belajar', 'baik', 100, 4000.00, 400000.00, '2020', '2025-11-19 02:45:25', '2025-11-19 02:50:51'),
(3, 38, 'Meja Guru', 'rusak_ringan', 10, 500000.00, 5000000.00, '2024', '2025-11-19 03:01:21', '2025-11-19 03:01:21');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `role` enum('admin','petugas','user') DEFAULT 'user',
  `status` enum('aktif','nonaktif') DEFAULT 'aktif',
  `foto` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `nama_lengkap`, `email`, `role`, `status`, `foto`, `created_at`, `updated_at`) VALUES
(3, 'kepsek', '$2y$10$5NQ6NqNrhQOnqKo2XbtS1uSDdQstQRa.kFq1oipD9fmIQ.MeBtL0a', 'Kepala Sekolah', 'kepsek@example.com', 'petugas', 'aktif', NULL, '2025-08-02 04:31:59', '2025-11-18 18:41:50'),
(5, 'kesni', '$2y$10$.6rb/dFMfSaPRS.dGrAhe.TujZ9QPb0w198wieSMITt2Iwy4iTtJW', 'Kesni', 'kesni@example.com', 'admin', 'aktif', NULL, '2025-08-04 14:11:14', '2025-11-18 18:38:49');

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_laporan_peminjaman`
-- (See below for the actual view)
--
CREATE TABLE `v_laporan_peminjaman` (
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_stok_barang`
-- (See below for the actual view)
--
CREATE TABLE `v_stok_barang` (
);

-- --------------------------------------------------------

--
-- Structure for view `v_laporan_peminjaman`
--
DROP TABLE IF EXISTS `v_laporan_peminjaman`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_laporan_peminjaman`  AS SELECT `p`.`id` AS `id`, `p`.`kode_peminjaman` AS `kode_peminjaman`, `b`.`kode_barang` AS `kode_barang`, `b`.`nama_barang` AS `nama_barang`, `p`.`peminjam_nama` AS `peminjam_nama`, `p`.`peminjam_kelas` AS `peminjam_kelas`, `p`.`peminjam_nis` AS `peminjam_nis`, `p`.`jumlah_pinjam` AS `jumlah_pinjam`, `p`.`tanggal_pinjam` AS `tanggal_pinjam`, `p`.`tanggal_kembali_rencana` AS `tanggal_kembali_rencana`, `p`.`tanggal_kembali_aktual` AS `tanggal_kembali_aktual`, `p`.`status` AS `status`, CASE WHEN `p`.`status` = 'dipinjam' AND curdate() > `p`.`tanggal_kembali_rencana` THEN 'terlambat' WHEN `p`.`status` = 'dikembalikan' THEN 'selesai' ELSE `p`.`status` END AS `status_detail`, to_days(coalesce(`p`.`tanggal_kembali_aktual`,curdate())) - to_days(`p`.`tanggal_pinjam`) AS `lama_peminjaman`, `u`.`nama_lengkap` AS `petugas` FROM ((`peminjaman` `p` join `barang` `b` on(`p`.`barang_id` = `b`.`id`)) join `users` `u` on(`p`.`created_by` = `u`.`id`)) ;

-- --------------------------------------------------------

--
-- Structure for view `v_stok_barang`
--
DROP TABLE IF EXISTS `v_stok_barang`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_stok_barang`  AS SELECT `b`.`id` AS `id`, `b`.`kode_barang` AS `kode_barang`, `b`.`nama_barang` AS `nama_barang`, `k`.`nama_kategori` AS `nama_kategori`, `l`.`nama_lokasi` AS `nama_lokasi`, `b`.`jumlah_total` AS `jumlah_total`, `b`.`jumlah_tersedia` AS `jumlah_tersedia`, `b`.`jumlah_total`- `b`.`jumlah_tersedia` AS `jumlah_dipinjam`, `b`.`kondisi` AS `kondisi`, `b`.`tahun_pengadaan` AS `tahun_pengadaan`, `b`.`harga_perolehan` AS `harga_perolehan`, `b`.`created_at` AS `created_at` FROM ((`barang` `b` join `kategori` `k` on(`b`.`kategori_id` = `k`.`id`)) join `lokasi` `l` on(`b`.`lokasi_id` = `l`.`id`)) ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `barang`
--
ALTER TABLE `barang`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_barang_kategori` (`kategori_id`);

--
-- Indexes for table `kategori`
--
ALTER TABLE `kategori`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pengaturan`
--
ALTER TABLE `pengaturan`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nama_pengaturan` (`nama_pengaturan`);

--
-- Indexes for table `transaksi`
--
ALTER TABLE `transaksi`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_barang_id` (`barang_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `barang`
--
ALTER TABLE `barang`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT for table `kategori`
--
ALTER TABLE `kategori`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `pengaturan`
--
ALTER TABLE `pengaturan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `transaksi`
--
ALTER TABLE `transaksi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `barang`
--
ALTER TABLE `barang`
  ADD CONSTRAINT `barang_ibfk_1` FOREIGN KEY (`kategori_id`) REFERENCES `kategori` (`id`);

--
-- Constraints for table `transaksi`
--
ALTER TABLE `transaksi`
  ADD CONSTRAINT `fk_transaksi_barang` FOREIGN KEY (`barang_id`) REFERENCES `barang` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
