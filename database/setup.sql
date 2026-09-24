-- Database & Skema Tabel untuk Dashboard Ekspor Komoditas Karantina
CREATE DATABASE IF NOT EXISTS db_ekspor_karantina CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE db_ekspor_karantina;

-- Tabel Transaksi Ekspor Komoditas
CREATE TABLE IF NOT EXISTS ekspor_komoditas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    jenis_kegiatan ENUM('ekspor','impor','domestik_keluar','domestik_masuk') NOT NULL DEFAULT 'ekspor',
    kategori ENUM('hewan','ikan','tumbuhan') NOT NULL,
    nama_komoditas VARCHAR(255) NOT NULL,
    nama_komoditas_lokal VARCHAR(255) NULL, -- khusus kategori ikan (nama lokal / ID)
    frekuensi INT DEFAULT 0,
    volume DECIMAL(18,2) DEFAULT 0,
    satuan VARCHAR(50) DEFAULT 'Kilogram',
    nilai_ekspor DECIMAL(20,2) DEFAULT 0,
    negara_tujuan TEXT NULL,
    periode VARCHAR(50) NOT NULL, -- contoh: '2026-01_2026-06'
    tanggal_mulai DATE NULL,
    tanggal_selesai DATE NULL,
    is_published TINYINT(1) DEFAULT 0,
    uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_kategori (kategori),
    INDEX idx_jenis_kegiatan (jenis_kegiatan),
    INDEX idx_jenis_kategori_pub (jenis_kegiatan, kategori, is_published),
    INDEX idx_is_published (is_published),
    INDEX idx_periode (periode)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel Pengaturan Tampilan per Kategori
CREATE TABLE IF NOT EXISTS ekspor_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    jenis_kegiatan ENUM('ekspor','impor','domestik_keluar','domestik_masuk') NOT NULL DEFAULT 'ekspor',
    kategori ENUM('hewan','ikan','tumbuhan') NOT NULL,
    top_n INT DEFAULT 5, -- 0 = tampilkan semua
    urutkan_berdasarkan ENUM('nilai_ekspor','frekuensi') DEFAULT 'nilai_ekspor',
    filter_mode ENUM('tahun','bulan','hari','semua') DEFAULT 'tahun',
    tanggal_awal DATE NULL,
    tanggal_akhir DATE NULL,
    tahun_awal SMALLINT NULL,
    tahun_akhir SMALLINT NULL,
    periode VARCHAR(50) NULL,
    periode_label VARCHAR(100) NULL, -- contoh: 'Januari - Juni 2026'
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_jenis_kategori_periode (jenis_kegiatan, kategori, periode)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel Admin Pengelola
CREATE TABLE IF NOT EXISTS admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    nama VARCHAR(100) NOT NULL,
    role ENUM('admin', 'user') NOT NULL DEFAULT 'admin',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed Default Settings
INSERT INTO ekspor_settings (jenis_kegiatan, kategori, top_n, urutkan_berdasarkan, filter_mode, tanggal_awal, tanggal_akhir, tahun_awal, tahun_akhir, periode_label)
VALUES 
('ekspor', 'hewan', 5, 'nilai_ekspor', 'tahun', '2026-01-01', '2026-12-31', 2026, 2026, 'Semester I 2026'),
('ekspor', 'ikan', 5, 'nilai_ekspor', 'tahun', '2026-01-01', '2026-12-31', 2026, 2026, 'Semester I 2026'),
('ekspor', 'tumbuhan', 5, 'nilai_ekspor', 'tahun', '2026-01-01', '2026-12-31', 2026, 2026, 'Tahun 2026')
ON DUPLICATE KEY UPDATE kategori=VALUES(kategori);
