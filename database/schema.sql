-- SIPANDA PTM - Skema Database
-- CATATAN: Database di sini HANYA untuk akun admin & log upload.
-- Data PTM (puskesmas, indikator, capaian) TIDAK disimpan di database --
-- dibaca langsung dari file Excel yang ada di folder uploads/ setiap kali dashboard diakses.

CREATE DATABASE IF NOT EXISTS sipanda_ptm CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sipanda_ptm;

-- Admin
CREATE TABLE admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    nama_lengkap VARCHAR(100)
);
-- Akun admin dibuat lewat setup.php (generate hash password otomatis), lalu setup.php dihapus.

-- Riwayat upload file Excel (metadata saja, bukan isi datanya)
CREATE TABLE upload_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_file_asli VARCHAR(255),
    jumlah_baris INT,
    diupload_oleh VARCHAR(100),
    tanggal_upload DATETIME DEFAULT CURRENT_TIMESTAMP
);
