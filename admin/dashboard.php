<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/excel_reader.php';
require_once __DIR__ . '/../vendor/autoload.php';
requireLogin();

$pdo = getDB();
$lastUpload = $pdo->query("SELECT * FROM upload_log ORDER BY id DESC LIMIT 1")->fetch();

$hasil = bacaDataSipanda(EXCEL_DATA_PATH);
$rows = $hasil['rows'];
$previewRows = array_slice($rows, -20);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Admin - SIPANDA PTM</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="admin-page">
    <nav class="admin-nav">
        <span>SIPANDA <b>PTM</b> — Admin</span>
        <div>
            <span>Halo, <?= htmlspecialchars($_SESSION['admin_nama']) ?></span>
            <a href="../index.php">Lihat Dashboard Publik</a>
            <a href="logout.php">Keluar</a>
        </div>
    </nav>

    <main class="admin-content">
        <h2>Kelola Data</h2>

        <div class="card-info">
            <p>Baris data valid pada file aktif saat ini: <b><?= count($rows) ?></b></p>
            <?php if ($hasil['errors']): ?>
                <p class="text-warning">Ada <?= count($hasil['errors']) ?> baris pada file yang dilewati (lihat detail di bawah).</p>
            <?php endif; ?>
            <?php if ($lastUpload): ?>
                <p>Upload terakhir: <b><?= htmlspecialchars($lastUpload['nama_file_asli']) ?></b>
                   (<?= $lastUpload['jumlah_baris'] ?> baris valid) oleh <?= htmlspecialchars($lastUpload['diupload_oleh']) ?>
                   — <?= $lastUpload['tanggal_upload'] ?></p>
            <?php else: ?>
                <p>Belum ada riwayat upload. Dashboard publik akan kosong sampai file pertama diupload.</p>
            <?php endif; ?>
        </div>

        <div class="upload-box">
            <h3>Upload Data Excel</h3>
            <p class="hint">
                File harus berupa .xlsx dengan sheet bernama <b>DATABASE SIPANDA</b>.<br>
                Kolom yang dipakai (header di baris 1): TAHUN, NO BULAN, BULAN, PUSKESMAS, INDIKATOR,
                SASARAN, TARGET TAHUNAN, TARGET BULANAN, CAPAIAN.
                PERSENTASE &amp; STATUS dihitung ulang otomatis oleh sistem, kolom CATATAN diabaikan.<br>
                <b>File ini menggantikan file sebelumnya</b> — data lama tidak disimpan di database, hanya file
                Excel terakhir yang dibaca dashboard.
            </p>
            <form action="upload_process.php" method="post" enctype="multipart/form-data" id="uploadForm">
                <input type="file" name="file_excel" accept=".xlsx" required>
                <button type="submit">Upload &amp; Ganti Data</button>
            </form>
            <div id="uploadResult"></div>
        </div>

        <div class="data-preview">
            <h3>Contoh Data dari File Aktif (20 baris terakhir)</h3>
            <table>
                <thead>
                <tr>
                    <th>Puskesmas</th><th>Indikator</th><th>Bulan</th>
                    <th>Sasaran</th><th>Target Bln</th><th>Capaian</th><th>%</th><th>Status</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($previewRows as $r): ?>
                <tr>
                    <td><?= htmlspecialchars($r['puskesmas']) ?></td>
                    <td><?= htmlspecialchars($r['indikator']) ?></td>
                    <td><?= namaBulan($r['bulan']) ?></td>
                    <td><?= $r['sasaran'] ?></td>
                    <td><?= $r['target_bulanan'] ?></td>
                    <td><?= $r['capaian'] ?></td>
                    <td><?= formatPersen($r['persentase']) ?></td>
                    <td><span class="badge" style="background:<?= warnaStatus($r['status']) ?>"><?= $r['status'] ?></span></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

            <?php if ($hasil['errors']): ?>
            <h3 style="margin-top:20px">Baris yang Dilewati</h3>
            <ul class="error-list">
                <?php foreach (array_slice($hasil['errors'], 0, 30) as $e): ?>
                    <li><?= htmlspecialchars($e) ?></li>
                <?php endforeach; ?>
            </ul>
            <?php endif; ?>
        </div>
    </main>

    <script src="../assets/js/admin.js"></script>
</body>
</html>
