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

$totalValid = count($rows);
$totalError = count($hasil['errors']);
$totalBaris = $totalValid + $totalError;
$pctValid = $totalBaris > 0 ? round(($totalValid / $totalBaris) * 100, 1) : 100;
$pctError = 100 - $pctValid;
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Admin - SIPANDA PTM</title>
<link rel="stylesheet" href="../assets/css/style.css">
<link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body class="admin-page">
    <nav class="admin-nav">
        <span class="sa-brand">
            <svg width="22" height="22" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg">
                <rect x="1" y="1" width="20" height="20" rx="6" fill="#0E7A63"/>
                <path d="M6.5 11.5L9.5 14.5L15.5 7.5" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            SIPANDA <b>PTM</b>
            <span class="sa-brand-tag">Admin</span>
        </span>
        <div class="sa-nav-right">
            <span>Halo, <?= htmlspecialchars($_SESSION['admin_nama']) ?></span>
            <a href="../index.php">Lihat Dashboard Publik</a>
            <a href="logout.php" class="sa-logout">Keluar</a>
        </div>
    </nav>

    <main class="admin-content">
        <h2>Kelola Data</h2>

        <div class="sa-info-row">
            <div class="sa-card">
                <p class="sa-eyebrow">Baris valid pada file aktif</p>
                <p class="sa-stat-value"><?= $totalValid ?></p>
                <div class="sa-quality-bar" role="img" aria-label="<?= $totalValid ?> baris valid, <?= $totalError ?> baris dilewati">
                    <span class="sa-qb-valid" style="width: <?= $pctValid ?>%"></span>
                    <?php if ($totalError > 0): ?>
                    <span class="sa-qb-error" style="width: <?= $pctError ?>%"></span>
                    <?php endif; ?>
                </div>
                <div class="sa-quality-legend">
                    <span><i class="sa-dot valid"></i><?= $totalValid ?> valid</span>
                    <?php if ($totalError > 0): ?>
                    <span><i class="sa-dot error"></i><?= $totalError ?> dilewati</span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="sa-card">
                <p class="sa-eyebrow">Upload Terakhir</p>
                <?php if ($lastUpload): ?>
                <div class="sa-upload-meta">
                    <span class="sa-filename"><?= htmlspecialchars($lastUpload['nama_file_asli']) ?></span><br>
                    <b><?= $lastUpload['jumlah_baris'] ?></b> baris valid ·
                    diupload oleh <b><?= htmlspecialchars($lastUpload['diupload_oleh']) ?></b><br>
                    <?= $lastUpload['tanggal_upload'] ?>
                </div>
                <?php else: ?>
                <p class="sa-empty-hint">Belum ada riwayat upload. Dashboard publik akan kosong sampai file pertama diupload.</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="sa-upload-box">
            <h3>Upload Data Excel</h3>
            <p class="sa-hint">
                File harus berupa .xlsx dengan sheet bernama <b>DATABASE SIPANDA</b>.
                Kolom yang dipakai (header di baris 1): TAHUN, NO BULAN, BULAN, PUSKESMAS, INDIKATOR,
                SASARAN, TARGET TAHUNAN, TARGET BULANAN, CAPAIAN.
                PERSENTASE &amp; STATUS dihitung ulang otomatis oleh sistem, kolom CATATAN diabaikan.
                <b>File ini menggantikan file sebelumnya</b> — data lama tidak disimpan di database, hanya file
                Excel terakhir yang dibaca dashboard.
            </p>
            <form action="upload_process.php" method="post" enctype="multipart/form-data" id="uploadForm">
                <label class="sa-dropzone" for="file_excel">
                    <svg width="26" height="26" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 15V3M12 3L7 8M12 3L17 8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M4 15V18C4 19.1046 4.89543 20 6 20H18C19.1046 20 20 19.1046 20 18V15" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <span class="sa-dropzone-text">
                        <span class="sa-dz-title">Pilih file Excel (.xlsx)</span>
                        <span class="sa-dz-sub" id="fileNamePreview">Belum ada file dipilih</span>
                    </span>
                </label>
                <input type="file" name="file_excel" id="file_excel" accept=".xlsx" required style="display:none">
                <button type="submit" class="sa-btn-primary">Upload &amp; Ganti Data</button>
            </form>
            <div id="uploadResult"></div>
        </div>

        <div class="sa-data-preview">
            <h3>Contoh Data dari File Aktif</h3>
            <p class="sa-eyebrow" style="margin-top:0">20 baris terakhir</p>
            <div class="sa-table-wrap">
                <table>
                    <thead>
                    <tr>
                        <th>Puskesmas</th><th>Indikator</th><th>Bulan</th>
                        <th class="sa-num">Sasaran</th><th class="sa-num">Target Bln</th>
                        <th class="sa-num">Capaian</th><th class="sa-num">%</th><th>Status</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($previewRows as $r): ?>
                    <tr>
                        <td><?= htmlspecialchars($r['puskesmas']) ?></td>
                        <td><?= htmlspecialchars($r['indikator']) ?></td>
                        <td><?= namaBulan($r['bulan']) ?></td>
                        <td class="sa-num"><?= $r['sasaran'] ?></td>
                        <td class="sa-num"><?= $r['target_bulanan'] ?></td>
                        <td class="sa-num"><?= $r['capaian'] ?></td>
                        <td class="sa-num"><?= formatPersen($r['persentase']) ?></td>
                        <td><span class="sa-badge" style="background:<?= warnaStatus($r['status']) ?>"><?= $r['status'] ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($hasil['errors']): ?>
            <div class="sa-error-panel">
                <h3>Baris yang Dilewati</h3>
                <p class="sa-error-count">Menampilkan <?= min(30, count($hasil['errors'])) ?> dari <?= count($hasil['errors']) ?> baris</p>
                <ul class="sa-error-list">
                    <?php foreach (array_slice($hasil['errors'], 0, 30) as $e): ?>
                        <li><?= htmlspecialchars($e) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
        </div>
    </main>

    <script>
        // Tampilkan nama file yang dipilih di dropzone (murni kosmetik,
        // tidak mengubah perilaku submit — itu tetap ditangani admin.js).
        document.getElementById('file_excel')?.addEventListener('change', function (e) {
            const preview = document.getElementById('fileNamePreview');
            if (e.target.files.length > 0) {
                preview.textContent = e.target.files[0].name;
            } else {
                preview.textContent = 'Belum ada file dipilih';
            }
        });
    </script>
    <script src="../assets/js/admin.js"></script>
</body>
</html>