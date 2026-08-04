<?php
// SIPANDA PTM - Konfigurasi Database
// Sesuaikan dengan environment kamu (XAMPP/hosting)

define('DB_HOST', 'localhost');
define('DB_NAME', 'sipanda_ptm');
define('DB_USER', 'root');
define('DB_PASS', 'juan123');

// Lokasi file Excel sumber data (ditimpa tiap kali admin upload file baru).
// Dashboard & API membaca langsung dari file ini, tidak ada tabel data di database.
define('EXCEL_DATA_PATH', __DIR__ . '/../uploads/data_sipanda.xlsx');

function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $pdo = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]
            );
        } catch (PDOException $e) {
            die("Koneksi database gagal: " . $e->getMessage());
        }
    }
    return $pdo;
}
