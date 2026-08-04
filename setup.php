<?php
// SIPANDA PTM - Jalankan SEKALI untuk membuat akun admin pertama, lalu HAPUS file ini.
require_once __DIR__ . '/config/database.php';

$username = 'admin';
$password = 'admin123'; // GANTI ini sebelum dijalankan di server produksi
$namaLengkap = 'Administrator';

$hash = password_hash($password, PASSWORD_BCRYPT);
$pdo = getDB();

$stmt = $pdo->prepare("INSERT INTO admin_users (username, password, nama_lengkap) VALUES (?, ?, ?)
    ON DUPLICATE KEY UPDATE password = VALUES(password)");
$stmt->execute([$username, $hash, $namaLengkap]);

echo "Akun admin berhasil dibuat/diperbarui.<br>";
echo "Username: $username<br>";
echo "Password: $password<br>";
echo "<strong>PENTING: hapus file setup.php ini sekarang juga.</strong>";
