<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
requireLogin();

$pdo = getDB();

// tandai dibaca (aksi simpel, POST id)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tandai_dibaca'])) {
    $stmt = $pdo->prepare('UPDATE feedback SET dibaca = 1 WHERE id = ?');
    $stmt->execute([(int) $_POST['tandai_dibaca']]);
    header('Location: feedback.php');
    exit;
}

// hapus feedback
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hapus'])) {
    $stmt = $pdo->prepare('DELETE FROM feedback WHERE id = ?');
    $stmt->execute([(int) $_POST['hapus']]);
    header('Location: feedback.php');
    exit;
}

$items = $pdo->query('SELECT * FROM feedback ORDER BY dibaca ASC, dibuat_pada DESC')->fetchAll();
$totalBelumDibaca = count(array_filter($items, fn($r) => (int) $r['dibaca'] === 0));
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Umpan Balik - Admin SIPANDA PTM</title>
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
            <a href="dashboard.php">Kelola Data</a>
            <a href="../index.php">Lihat Dashboard Publik</a>
            <a href="logout.php" class="sa-logout">Keluar</a>
        </div>
    </nav>

    <main class="admin-content">
        <h2>Umpan Balik <?php if ($totalBelumDibaca > 0): ?><span class="sa-badge" style="background:#C0392B"><?= $totalBelumDibaca ?> belum dibaca</span><?php endif; ?></h2>

        <?php if (empty($items)): ?>
            <div class="sa-card">
                <p class="sa-empty-hint">Belum ada umpan balik masuk.</p>
            </div>
        <?php else: ?>
            <div class="sa-table-wrap">
                <table>
                    <thead>
                    <tr>
                        <th>Tanggal</th><th>Nama</th><th>Email</th><th>Puskesmas</th><th>Pesan</th><th>Status</th><th></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($items as $r): ?>
                    <tr style="<?= (int)$r['dibaca'] === 0 ? 'font-weight:600' : 'opacity:.7' ?>">
                        <td><?= htmlspecialchars($r['dibuat_pada']) ?></td>
                        <td><?= htmlspecialchars($r['nama']) ?></td>
                        <td><?= $r['email'] ? htmlspecialchars($r['email']) : '<span style="color:#999">-</span>' ?></td>
                        <td><?= $r['puskesmas_asal'] ? htmlspecialchars(ucwords(strtolower($r['puskesmas_asal']))) : '<span style="color:#999">Umum</span>' ?></td>
                        <td style="white-space:pre-wrap;max-width:360px"><?= htmlspecialchars($r['pesan']) ?></td>
                        <td>
                            <?php if ((int)$r['dibaca'] === 1): ?>
                                <span class="sa-badge" style="background:#0E7A63">Dibaca</span>
                            <?php else: ?>
                                <span class="sa-badge" style="background:#C0392B">Baru</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ((int)$r['dibaca'] === 0): ?>
                            <form method="post" style="margin:0;display:inline-block">
                                <input type="hidden" name="tandai_dibaca" value="<?= (int)$r['id'] ?>">
                                <button type="submit" class="sa-btn-primary" style="padding:4px 10px;font-size:.85em">Tandai Dibaca</button>
                            </form>
                            <?php endif; ?>
                            <form method="post" style="margin:0;display:inline-block" onsubmit="return confirm('Hapus umpan balik dari <?= htmlspecialchars(addslashes($r['nama'])) ?> ini? Tidak bisa dibatalkan.')">
                                <input type="hidden" name="hapus" value="<?= (int)$r['id'] ?>">
                                <button type="submit" style="padding:4px 10px;font-size:.85em;background:#C0392B;color:#fff;border:none;border-radius:6px;cursor:pointer;margin-left:6px">Hapus</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>