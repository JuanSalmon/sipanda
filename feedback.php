<?php
// SIPANDA PTM - Umpan Balik (publik)
require_once __DIR__ . '/config/database.php';

const DAFTAR_PUSKESMAS = [
    'BAA', 'BATUTUA', 'BUSALANGGA', 'DELHA', 'EAHUN', 'FEAPOPI',
    'KORBAFO', 'NDAO', 'OELABA', 'OELE', 'SONIMANU', 'SOTIMORI',
];

$sukses = false;
$errorMsg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $puskesmasAsal = trim($_POST['puskesmas_asal'] ?? '');
    $pesan = trim($_POST['pesan'] ?? '');

    if ($nama === '' || $pesan === '') {
        $errorMsg = 'Nama dan pesan wajib diisi.';
    } elseif (mb_strlen($nama) > 100 || mb_strlen($pesan) > 3000) {
        $errorMsg = 'Nama atau pesan terlalu panjang.';
    } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errorMsg = 'Format email tidak valid.';
    } elseif ($puskesmasAsal !== '' && !in_array($puskesmasAsal, DAFTAR_PUSKESMAS, true)) {
        $errorMsg = 'Puskesmas asal tidak valid.';
    } else {
        try {
            $pdo = getDB();
            $stmt = $pdo->prepare('INSERT INTO feedback (nama, email, puskesmas_asal, pesan) VALUES (?, ?, ?, ?)');
            $stmt->execute([$nama, $email !== '' ? $email : null, $puskesmasAsal !== '' ? $puskesmasAsal : null, $pesan]);
            $sukses = true;
        } catch (PDOException $e) {
            $errorMsg = 'Gagal mengirim umpan balik. Coba lagi nanti.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Umpan Balik - SIPANDA PTM</title>
<link rel="icon" type="image/png" href="assets/img/logo-mark.png">
<link rel="stylesheet" href="assets/css/style.css?v=<?= filemtime(__DIR__ . '/assets/css/style.css') ?>">
<link rel="stylesheet" href="assets/css/sidebar.css?v=<?= filemtime(__DIR__ . '/assets/css/sidebar.css') ?>">
<link rel="stylesheet" href="assets/css/feedback.css?v=<?= filemtime(__DIR__ . '/assets/css/feedback.css') ?>">
</head>
<body>
    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

    <div class="app-shell">
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-brand">
                <div class="sidebar-brand-mark">
                    <img src="assets/img/logo-mark.png" alt="SIPANDA PTM">
                </div>
                <div class="sidebar-brand-text">
                    <div class="sidebar-brand-title">SIPANDA <span>PTM</span></div>
                    <div class="sidebar-brand-sub">Sarana Informasi Penyajian dan Analisis Data Penyakit Tidak Menular</div>
                </div>
                <button class="sidebar-collapse-btn" id="sidebarCollapse" type="button" aria-expanded="true" aria-label="Ciutkan sidebar" title="Ciutkan sidebar">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
                </button>
            </div>

            <nav class="sidebar-nav">
                <a class="sidebar-link" href="index.php" title="Dashboard Kabupaten">
                    <span class="sidebar-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 11l9-8 9 8"/><path d="M5 10v10h14V10"/></svg></span>
                    <span class="sidebar-label">Dashboard Kabupaten</span>
                </a>

                <a class="sidebar-link" href="puskesmas.php" title="Dashboard Puskesmas">
                    <span class="sidebar-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="10" width="18" height="11" rx="1"/><path d="M9 21V13h6v8"/><path d="M12 3l9 7H3l9-7z"/></svg></span>
                    <span class="sidebar-label">Dashboard Puskesmas</span>
                </a>

                <a class="sidebar-link is-active" href="feedback.php" title="Umpan Balik">
                    <span class="sidebar-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg></span>
                    <span class="sidebar-label">Umpan Balik</span>
                </a>
            </nav>

            <div class="sidebar-footer">
                <a class="sidebar-footer-link" href="#" data-open-login title="Login Admin">
                    <span class="sidebar-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="11" width="14" height="9" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg></span>
                    <span class="sidebar-label">Login Admin</span>
                </a>
            </div>
        </aside>

        <div class="app-main">
            <header class="topbar">
                <button class="sidebar-toggle" id="sidebarToggle" type="button" aria-label="Buka menu" aria-expanded="false">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="4" y1="7" x2="20" y2="7"/><line x1="4" y1="12" x2="20" y2="12"/><line x1="4" y1="17" x2="20" y2="17"/></svg>
                </button>
                <div class="brand">SIPANDA <span>PTM</span></div>
                <div class="brand-sub">Umpan Balik</div>
            </header>

            <main class="dashboard">
                <div class="fb-wrap">
                    <div class="fb-header">
                        <span class="fb-header-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
                        </span>
                        <div>
                            <h2>Umpan Balik</h2>
                            <p>Punya masukan, pertanyaan, atau laporan soal data di dashboard ini? Admin akan membacanya.</p>
                        </div>
                    </div>

                    <?php if ($sukses): ?>
                        <div class="fb-banner fb-banner--success">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M8 12.5l2.5 2.5L16 9.5"/></svg>
                            <div>
                                <p class="fb-banner-title">Terkirim</p>
                                <p>Terima kasih, umpan balik kamu sudah diterima.</p>
                                <a class="fb-again" href="feedback.php">Kirim umpan balik lain</a>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="fb-card">
                            <?php if ($errorMsg): ?>
                                <div class="fb-banner fb-banner--error" style="margin-top:0;margin-bottom:20px;">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="13"/><line x1="12" y1="16" x2="12" y2="16.1"/></svg>
                                    <div>
                                        <p class="fb-banner-title">Gagal mengirim</p>
                                        <p><?= htmlspecialchars($errorMsg) ?></p>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <form method="post" id="fbForm">
                                <div class="fb-field">
                                    <label class="fb-label" for="fbNama">Nama <span class="fb-req">*</span></label>
                                    <input class="fb-input" type="text" id="fbNama" name="nama" required maxlength="100"
                                        placeholder="Nama kamu"
                                        value="<?= htmlspecialchars($_POST['nama'] ?? '') ?>">
                                </div>
                                <div class="fb-field">
                                    <label class="fb-label" for="fbEmail">Email <span class="fb-optional">(opsional)</span></label>
                                    <input class="fb-input" type="email" id="fbEmail" name="email" maxlength="150"
                                           placeholder="nama@contoh.com"
                                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                                </div>
                                <div class="fb-field">
                                    <label class="fb-label" for="fbPuskesmas">Asal Puskesmas <span class="fb-optional">(opsional)</span></label>
                                    <select class="fb-input" id="fbPuskesmas" name="puskesmas_asal">
                                        <option value="">— Umum / tidak terkait Puskesmas tertentu —</option>
                                        <?php foreach (DAFTAR_PUSKESMAS as $pk): ?>
                                            <option value="<?= htmlspecialchars($pk) ?>" <?= ($_POST['puskesmas_asal'] ?? '') === $pk ? 'selected' : '' ?>>
                                                <?= htmlspecialchars(ucwords(strtolower($pk))) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="fb-field">
                                    <label class="fb-label" for="fbPesan">Saran <span class="fb-req">*</span></label>
                                    <textarea class="fb-textarea" id="fbPesan" name="pesan" required maxlength="3000" rows="5"
                                            placeholder="Tulis saran kamu untuk dashboard ini di sini..."><?= htmlspecialchars($_POST['pesan'] ?? '') ?></textarea>
                                    <div class="fb-counter" id="fbCounter">0 / 3000</div>
                                </div>
                                <button type="submit" class="fb-submit">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                                    Kirim Umpan Balik
                                </button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>

    <div class="login-modal-backdrop" id="loginModalBackdrop">
        <div class="login-modal-card" role="dialog" aria-modal="true" aria-labelledby="loginModalTitle">
            <button type="button" class="login-modal-close" id="loginModalClose" aria-label="Tutup">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
            <img class="login-modal-logo" src="assets/img/logo-mark.png" alt="SIPANDA PTM">
            <h2 id="loginModalTitle">Login Admin</h2>
            <p class="login-modal-sub">Masuk untuk mengelola data SIPANDA PTM</p>
            <div class="login-modal-error" id="loginModalError" hidden></div>
            <form id="loginModalForm">
                <label for="loginUsername">Username</label>
                <input type="text" id="loginUsername" name="username" required autocomplete="username">
                <label for="loginPassword">Password</label>
                <input type="password" id="loginPassword" name="password" required autocomplete="current-password">
                <button type="submit" id="loginModalSubmit">Masuk</button>
            </form>
        </div>
    </div>

    <script src="assets/js/sidebar.js?v=<?= filemtime(__DIR__ . '/assets/js/sidebar.js') ?>"></script>
    <script src="assets/js/login-modal.js?v=<?= filemtime(__DIR__ . '/assets/js/login-modal.js') ?>"></script>
    <script>
        (function () {
            var pesan = document.getElementById('fbPesan');
            var counter = document.getElementById('fbCounter');
            if (!pesan || !counter) return;
            var max = 3000;
            function update() {
                var len = pesan.value.length;
                counter.textContent = len + ' / ' + max;
                counter.classList.toggle('is-near-limit', len > max * 0.9);
            }
            pesan.addEventListener('input', update);
            update();
        })();
    </script>
</body>
</html>