<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SIPANDA PTM - Dashboard Puskesmas</title>
<link rel="icon" type="image/png" href="assets/img/logo-mark.png">
<link rel="stylesheet" href="assets/css/style.css?v=<?= filemtime(__DIR__ . '/assets/css/style.css') ?>">
<link rel="stylesheet" href="assets/css/sidebar.css?v=<?= filemtime(__DIR__ . '/assets/css/sidebar.css') ?>">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
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

                <a class="sidebar-link is-active" href="puskesmas.php" title="Dashboard Puskesmas">
                    <span class="sidebar-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="10" width="18" height="11" rx="1"/><path d="M9 21V13h6v8"/><path d="M12 3l9 7H3l9-7z"/></svg></span>
                    <span class="sidebar-label">Dashboard Puskesmas</span>
                </a>

                <div class="sidebar-group is-expanded">
                    <button class="sidebar-group-toggle" type="button" aria-expanded="true" title="Puskesmas Ini">
                        <span class="sidebar-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="6" y1="20" x2="6" y2="12"/><line x1="12" y1="20" x2="12" y2="6"/><line x1="18" y1="20" x2="18" y2="14"/></svg></span>
                        <span class="sidebar-label">Puskesmas Ini</span>
                        <span class="sidebar-caret">›</span>
                    </button>
                    <div class="sidebar-submenu"><div>
                        <a href="#pkmInfo" data-target="pkmInfo">Info Puskesmas</a>
                        <a href="#pkmKpi" data-target="pkmKpi">KPI Indikator</a>
                        <a href="#pkmProgress" data-target="pkmProgress">Progress Capaian</a>
                        <a href="#pkmTrend" data-target="pkmTrend">Tren Bulanan</a>
                        <a href="#pkmStatusTable" data-target="pkmStatusTable">Status Indikator</a>
                    </div></div>
                </div>
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
                <div class="brand-sub">Dashboard Puskesmas</div>
            </header>

            <main class="dashboard" id="top">

                <section class="toolbar-card" aria-label="Filter dashboard puskesmas">
                    <div class="toolbar-title">Filter Puskesmas</div>
                    <div class="toolbar-controls">
                        <label class="filter-control">
                            <span class="filter-label">Puskesmas</span>
                            <select id="pkmSelect" class="filter-select"></select>
                        </label>
                        <label class="filter-control">
                            <span class="filter-label">Periode</span>
                            <select id="pkmPeriodeType" class="filter-select filter-select--small">
                                <option value="bulanan">Bulanan</option>
                                <option value="triwulan">Triwulan</option>
                                <option value="semester">Semester</option>
                                <option value="tahunan">Tahunan</option>
                            </select>
                        </label>
                        <label class="filter-control">
                            <span class="filter-label">Nilai Periode</span>
                            <select id="pkmPeriodeValue" class="filter-select">
                                <option value="all">Semua Periode</option>
                            </select>
                        </label>
                    </div>
                </section>

                <p class="dashboard-section-title" id="pkmInfo">Info Puskesmas</p>
                <section class="charts-row" style="grid-template-columns: 1fr;">
                    <div class="chart-card" id="pkmInfoCard">
                        <!-- diisi via JS -->
                    </div>
                </section>

                <p class="dashboard-section-title" id="pkmKpi">KPI Indikator</p>
                <section class="scoreboards" id="pkmScoreboards">
                    <!-- 4 kartu KPI diisi via JS -->
                </section>

                <p class="dashboard-section-title" id="pkmProgress">Progress Capaian Indikator</p>
                <section class="chart-card" id="pkmProgressCard">
                    <!-- progress bar per indikator diisi via JS -->
                </section>

                <p class="dashboard-section-title" id="pkmTrend">Tren Bulanan</p>
                <section class="charts-row" style="grid-template-columns: 1fr;">
                    <div class="chart-card">
                        <h3>Tren Capaian per Bulan - Puskesmas Terpilih</h3>
                        <div class="chart-canvas-box chart-canvas-box--short">
                            <canvas id="pkmTrendChart"></canvas>
                        </div>
                    </div>
                </section>

                <p class="dashboard-section-title" id="pkmStatusTable">Status 4 Indikator</p>
                <section class="table-section">
                    <div class="chart-card table-card">
                        <div class="table-wrap">
                            <table id="pkmStatusTableEl">
                                <thead>
                                    <tr><th>Indikator</th><th>Capaian/Target</th><th>%</th><th>Status</th></tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
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

    <script src="assets/js/puskesmas.js?v=<?= filemtime(__DIR__ . '/assets/js/puskesmas.js') ?>"></script>
    <script src="assets/js/sidebar.js?v=<?= filemtime(__DIR__ . '/assets/js/sidebar.js') ?>"></script>
    <script src="assets/js/login-modal.js?v=<?= filemtime(__DIR__ . '/assets/js/login-modal.js') ?>"></script>
</body>
</html>
