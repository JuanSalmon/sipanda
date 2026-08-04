<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/excel_reader.php';
require_once __DIR__ . '/../vendor/autoload.php';
requireLogin();

header('Content-Type: application/json');

if (!isset($_FILES['file_excel']) || $_FILES['file_excel']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'File tidak ditemukan atau gagal diupload.']);
    exit;
}

$tmpPath = $_FILES['file_excel']['tmp_name'];
$namaFileAsli = $_FILES['file_excel']['name'];
$ext = strtolower(pathinfo($namaFileAsli, PATHINFO_EXTENSION));

if ($ext !== 'xlsx') {
    echo json_encode(['success' => false, 'message' => 'Hanya file .xlsx yang diperbolehkan.']);
    exit;
}

// Validasi dulu dari file sementara SEBELUM menimpa file yang aktif dipakai dashboard,
// supaya kalau file rusak/salah format, data lama yang masih tampil di dashboard tidak ikut hilang.
$hasil = bacaDataSipanda($tmpPath);

if (empty($hasil['rows']) && !empty($hasil['errors'])) {
    // Gagal total (misal sheet tidak ada / kolom wajib hilang)
    echo json_encode(['success' => false, 'message' => $hasil['errors'][0]]);
    exit;
}

// Lolos validasi -> simpan file baru ke arsip, lalu aktifkan salinannya sebagai file yang dibaca dashboard.
$uploadDir = dirname(EXCEL_DATA_PATH);
$archiveDir = $uploadDir . '/history';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
if (!is_dir($archiveDir)) mkdir($archiveDir, 0755, true);

$fileNameSafe = preg_replace('/[^A-Za-z0-9._-]+/', '_', pathinfo($namaFileAsli, PATHINFO_FILENAME));
$fileNameSafe = $fileNameSafe !== '' ? $fileNameSafe : 'excel-data';
$archivePath = $archiveDir . '/' . date('YmdHis') . '_' . $fileNameSafe . '.' . $ext;

if (!move_uploaded_file($tmpPath, $archivePath)) {
    echo json_encode(['success' => false, 'message' => 'Gagal menyimpan file arsip ke server.']);
    exit;
}

if (!copy($archivePath, EXCEL_DATA_PATH)) {
    echo json_encode(['success' => false, 'message' => 'Gagal mengaktifkan file Excel baru untuk dashboard.']);
    exit;
}

try {
    $pdo = getDB();
    $logStmt = $pdo->prepare("INSERT INTO upload_log (nama_file_asli, jumlah_baris, diupload_oleh) VALUES (?,?,?)");
    $logStmt->execute([$namaFileAsli, count($hasil['rows']), $_SESSION['admin_nama'] ?? 'admin']);
} catch (Exception $e) {
    // Log gagal bukan alasan untuk gagalkan upload; file sudah tersimpan dan dashboard tetap jalan.
}

echo json_encode([
    'success' => true,
    'message' => "File berhasil diupload. " . count($hasil['rows']) . " baris data valid ditemukan.",
    'errors' => $hasil['errors'],
]);
