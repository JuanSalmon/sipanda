<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SIPANDA PTM - Panduan Penggunaan</title>
<link rel="icon" type="image/png" href="assets/img/logo-mark.png">
<link rel="stylesheet" href="assets/css/style.css?v=<?= filemtime(__DIR__ . '/assets/css/style.css') ?>">
<link rel="stylesheet" href="assets/css/sidebar.css?v=<?= filemtime(__DIR__ . '/assets/css/sidebar.css') ?>">
<style>
    .panduan-steps { margin: 0; padding-left: 22px; }
    .panduan-steps > li { margin-bottom: 16px; }
    .panduan-steps > li:last-child { margin-bottom: 0; }
    .panduan-steps strong { font-family: 'Space Grotesk', sans-serif; font-size: 15px; }
    .panduan-steps p { margin: 4px 0 0; color: var(--muted); }

    .panduan-status-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 14px;
        margin-top: 14px;
    }
    @media (max-width: 900px) { .panduan-status-grid { grid-template-columns: 1fr; } }
    .panduan-status-card {
        border-radius: var(--radius-sm);
        padding: 14px 16px;
        border: 1px solid var(--border);
        background: var(--card-sunken);
    }
    .panduan-status-card strong { display: block; font-family: 'Space Grotesk', sans-serif; margin: 6px 0 4px; font-size: 14.5px; }
    .panduan-status-card p { margin: 0; font-size: 13px; color: var(--muted); }
    .panduan-status-badge { display: inline-block; font-weight: 700; font-size: 13px; padding: 3px 10px; border-radius: 999px; color: #fff; }
    .panduan-status-card--green .panduan-status-badge { background: var(--green); }
    .panduan-status-card--green { border-color: color-mix(in srgb, var(--green) 35%, var(--border)); }
    .panduan-status-card--amber .panduan-status-badge { background: var(--amber); }
    .panduan-status-card--amber { border-color: color-mix(in srgb, var(--amber) 35%, var(--border)); }
    .panduan-status-card--red .panduan-status-badge { background: var(--red); }
    .panduan-status-card--red { border-color: color-mix(in srgb, var(--red) 35%, var(--border)); }
    .panduan-status-card--gray .panduan-status-badge { background: #94a3b8; }

    .panduan-example-table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 13.5px; }
    .panduan-example-table th, .panduan-example-table td { padding: 9px 12px; border-bottom: 1px solid var(--border); text-align: left; }
    .panduan-example-table .badge { color: #fff; padding: 3px 10px; border-radius: 999px; font-size: 12px; font-weight: 600; }

    .panduan-tips-list { margin: 0; padding-left: 20px; }
    .panduan-tips-list li { margin-bottom: 8px; color: var(--muted); }
    .panduan-tips-list li:last-child { margin-bottom: 0; }
</style>
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

                <div class="sidebar-group is-expanded">
                    <button class="sidebar-group-toggle" type="button" aria-expanded="true" title="Panduan">
                        <span class="sidebar-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M9.5 9.5a2.5 2.5 0 0 1 4.9.8c0 1.7-2.4 1.7-2.4 3.2"/><line x1="12" y1="17" x2="12" y2="17.1"/></svg></span>
                        <span class="sidebar-label">Isi Panduan</span>
                        <span class="sidebar-caret">›</span>
                    </button>
                    <div class="sidebar-submenu"><div>
                        <a href="#panduan-cara-pakai" data-target="panduan-cara-pakai">Cara Menggunakan</a>
                        <a href="#panduan-grafik" data-target="panduan-grafik">Arti Setiap Grafik</a>
                        <a href="#panduan-status" data-target="panduan-status">Status Capaian</a>
                        <a href="#panduan-tips" data-target="panduan-tips">Tips Penggunaan</a>
                    </div></div>
                </div>
            </nav>

            <div class="sidebar-footer">
                <a class="sidebar-footer-link is-active" href="panduan.php" title="Panduan">
                    <span class="sidebar-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M9.5 9.5a2.5 2.5 0 0 1 4.9.8c0 1.7-2.4 1.7-2.4 3.2"/><line x1="12" y1="17" x2="12" y2="17.1"/></svg></span>
                    <span class="sidebar-label">Panduan</span>
                </a>
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
                <div class="brand-sub">Panduan Penggunaan</div>
            </header>

            <main class="dashboard" id="top">

                <p class="dashboard-section-title" id="panduan-umum">Tentang SIPANDA PTM</p>
                <section class="charts-row" style="grid-template-columns: 1fr;">
                    <div class="chart-card">
                        <p>SIPANDA PTM (Sarana Informasi Penyajian dan Analisis Data Penyakit Tidak
                        Menular) adalah media monitoring dan evaluasi capaian indikator Program PTM
                        secara cepat, akurat, dan mudah dipahami &mdash; untuk 12 Puskesmas di
                        Kabupaten Rote Ndao, 4 indikator (Usia Produktif, Hipertensi, Diabetes
                        Mellitus, HPV DNA Co Testing IVA), dibandingkan terhadap target tahunan
                        masing-masing.</p>
                    </div>
                </section>

                <p class="dashboard-section-title" id="panduan-cara-pakai">Cara Menggunakan Dashboard</p>
                <section class="charts-row" style="grid-template-columns: 1fr;">
                    <div class="chart-card">
                        <ol class="panduan-steps">
                            <li>
                                <strong>Pilih Puskesmas</strong>
                                <p>Di Dashboard Kabupaten, biarkan pada &ldquo;Semua Puskesmas&rdquo; untuk
                                melihat ringkasan se-kabupaten, atau pilih satu Puskesmas untuk fokus ke
                                capaiannya. Di Dashboard Puskesmas, pilih Puskesmas lewat dropdown di
                                bagian atas halaman.</p>
                            </li>
                            <li>
                                <strong>Pilih Jenis Periode</strong>
                                <p>Tentukan cara membaca waktu: <em>Bulanan</em>, <em>Triwulan</em>,
                                <em>Semester</em>, atau <em>Tahunan</em>.</p>
                            </li>
                            <li>
                                <strong>Pilih Nilai Periode</strong>
                                <p>Sesuaikan dengan jenis periode yang dipilih &mdash; contoh: pilih
                                &ldquo;Januari&rdquo; untuk periode Bulanan, atau &ldquo;Semua
                                Periode&rdquo; untuk melihat keseluruhan data yang tersedia.</p>
                            </li>
                            <li>
                                <strong>Dashboard Diperbarui Otomatis</strong>
                                <p>Seluruh kartu skor, grafik, dan tabel di halaman langsung
                                menyesuaikan begitu filter diubah &mdash; termasuk target yang
                                ditampilkan, yang otomatis diproporsikan sesuai periode dipilih (mis.
                                satu bulan = 1/12 dari target tahunan).</p>
                            </li>
                        </ol>
                    </div>
                </section>

                <p class="dashboard-section-title" id="panduan-grafik">Arti Setiap Grafik</p>
                <section class="charts-row" style="grid-template-columns: 1fr;">
                    <div class="chart-card">
                        <h3>Ringkasan Capaian (Score Card)</h3>
                        <p>Kartu skor per indikator menampilkan persentase capaian terhadap target
                        beserta status warnanya. Tujuan: melihat capaian tiap indikator secara cepat.</p>

                        <h3>Tren Capaian</h3>
                        <p>Grafik garis perkembangan capaian tiap indikator per bulan. Tujuan: melihat
                        kecenderungan peningkatan atau penurunan capaian dari waktu ke waktu.</p>

                        <h3>Ranking Indikator &amp; Ranking Puskesmas</h3>
                        <p>Mengurutkan indikator atau Puskesmas berdasarkan persentase capaian
                        (skor gabungan lintas indikator, untuk ranking Puskesmas). Tujuan: mengetahui
                        capaian tertinggi maupun yang masih memerlukan pembinaan. Klik nama/bar
                        Puskesmas pada Ranking Puskesmas untuk langsung membuka Dashboard Puskesmas
                        terkait.</p>

                        <h3>Distribusi Status</h3>
                        <p>Diagram donat jumlah Puskesmas per kategori status capaian. Tujuan:
                        memberikan gambaran kondisi capaian Program PTM secara keseluruhan.</p>
                    </div>
                </section>

                <p class="dashboard-section-title" id="panduan-status">Status Capaian</p>
                <section class="charts-row" style="grid-template-columns: 1fr;">
                    <div class="chart-card">
                        <p>Status ditentukan dari persentase capaian terhadap target pada periode
                        terpilih:</p>
                        <div class="panduan-status-grid">
                            <div class="panduan-status-card panduan-status-card--green">
                                <div class="panduan-status-badge">&ge; 100%</div>
                                <strong>Tercapai</strong>
                                <p>Capaian indikator telah memenuhi target yang ditetapkan dan perlu
                                dipertahankan.</p>
                            </div>
                            <div class="panduan-status-card panduan-status-card--amber">
                                <div class="panduan-status-badge">70% &ndash; 99,99%</div>
                                <strong>Perlu Ditingkatkan</strong>
                                <p>Capaian sudah menunjukkan perkembangan, namun masih memerlukan
                                upaya peningkatan agar mencapai target.</p>
                            </div>
                            <div class="panduan-status-card panduan-status-card--red">
                                <div class="panduan-status-badge">&lt; 70%</div>
                                <strong>Belum Tercapai</strong>
                                <p>Capaian indikator masih rendah sehingga memerlukan perhatian dan
                                tindak lanjut.</p>
                            </div>
                            <div class="panduan-status-card panduan-status-card--gray">
                                <div class="panduan-status-badge">&ndash;</div>
                                <strong>Tidak Ada Target</strong>
                                <p>Indikator belum memiliki data target pada periode ini (netral,
                                bukan berarti capaian buruk).</p>
                            </div>
                        </div>

                        <h3 style="margin-top:22px;">Contoh Interpretasi</h3>
                        <div class="table-wrap">
                            <table class="panduan-example-table">
                                <thead><tr><th>Target</th><th>Capaian</th><th>Persentase</th><th>Status</th></tr></thead>
                                <tbody>
                                    <tr><td>1.000</td><td>1.050</td><td>105%</td><td><span class="badge" style="background:#22c55e">Tercapai</span></td></tr>
                                    <tr><td>1.000</td><td>800</td><td>80%</td><td><span class="badge" style="background:#f59e0b">Perlu Ditingkatkan</span></td></tr>
                                    <tr><td>1.000</td><td>450</td><td>45%</td><td><span class="badge" style="background:#ef4444">Belum Tercapai</span></td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>

                <p class="dashboard-section-title" id="panduan-tips">Tips Penggunaan</p>
                <section class="charts-row" style="grid-template-columns: 1fr;">
                    <div class="chart-card">
                        <ul class="panduan-tips-list">
                            <li>Gunakan filter Puskesmas, Jenis Periode, dan Nilai Periode untuk
                            menampilkan data sesuai kebutuhan.</li>
                            <li>Dashboard memperbarui seluruh informasi secara otomatis begitu filter
                            diubah.</li>
                            <li>Perhatikan Score Card untuk mengetahui capaian indikator secara
                            ringkas.</li>
                            <li>Gunakan Tren Capaian untuk memantau perkembangan capaian dari waktu ke
                            waktu.</li>
                            <li>Manfaatkan Ranking Puskesmas sebagai dasar pembinaan dan tindak lanjut
                            Program PTM.</li>
                        </ul>
                    </div>
                </section>

                <p class="dashboard-section-title" id="panduan-admin">Untuk Admin</p>
                <section class="charts-row" style="grid-template-columns: 1fr;">
                    <div class="chart-card">
                        <p>Admin login lewat tombol <strong>Login Admin</strong> di sidebar untuk
                        mengunggah data Excel terbaru (format <code>MASTER_SIPANDA</code>). Setelah
                        diunggah, seluruh dashboard otomatis memperbarui data berdasarkan file
                        tersebut.</p>
                    </div>
                </section>

            </main>

            <footer class="footer">SIPANDA PTM &copy; 2026</footer>
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
</body>
</html>