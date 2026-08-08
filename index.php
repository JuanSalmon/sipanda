<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SIPANDA PTM - Dashboard Monitoring</title>
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
                <a class="sidebar-link is-active" href="#top" data-target="top" title="Dashboard">
                    <span class="sidebar-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 11l9-8 9 8"/><path d="M5 10v10h14V10"/></svg></span>
                    <span class="sidebar-label">Dashboard</span>
                </a>

                <div class="sidebar-group is-expanded">
                    <button class="sidebar-group-toggle" type="button" aria-expanded="true" title="Monitoring">
                        <span class="sidebar-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="6" y1="20" x2="6" y2="12"/><line x1="12" y1="20" x2="12" y2="6"/><line x1="18" y1="20" x2="18" y2="14"/></svg></span>
                        <span class="sidebar-label">Monitoring</span>
                        <span class="sidebar-caret">›</span>
                    </button>
                    <div class="sidebar-submenu"><div>
                        <a href="#scoreboards" data-target="scoreboards">Ringkasan Capaian</a>
                        <a href="#monitorTable" data-target="monitorTable">Monitoring per Puskesmas</a>
                        <a href="#heatmapTable" data-target="heatmapTable">Monitoring Bulanan</a>
                    </div></div>
                </div>

                <div class="sidebar-group">
                    <button class="sidebar-group-toggle" type="button" aria-expanded="false" title="Analisis">
                        <span class="sidebar-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 17 9 11 13 15 21 6"/><polyline points="15 6 21 6 21 12"/></svg></span>
                        <span class="sidebar-label">Analisis</span>
                        <span class="sidebar-caret">›</span>
                    </button>
                    <div class="sidebar-submenu"><div>
                        <a href="#lineChart" data-target="lineChart">Tren Capaian</a>
                        <a href="#comboChart" data-target="comboChart">Target vs Capaian</a>
                        <a href="#indikatorRankChart" data-target="indikatorRankChart">Ranking Indikator</a>
                        <a href="#barChart" data-target="barChart">Ranking Puskesmas</a>
                        <a href="#doughnutChart" data-target="doughnutChart">Distribusi Status</a>
                    </div></div>
                </div>

                <div class="sidebar-group">
                    <button class="sidebar-group-toggle" type="button" aria-expanded="false" title="Data PTM">
                        <span class="sidebar-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="6" y="4" width="12" height="17" rx="2"/><path d="M9 4V3a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v1"/><line x1="9" y1="11" x2="15" y2="11"/><line x1="9" y1="15" x2="15" y2="15"/></svg></span>
                        <span class="sidebar-label">Data PTM</span>
                        <span class="sidebar-caret">›</span>
                    </button>
                    <div class="sidebar-submenu"><div>
                        <a href="#" data-open-login>Data Capaian</a>
                        <a href="#" data-open-login>Data Target</a>
                        <a href="#" data-open-login>Data Puskesmas</a>
                        <a href="#" data-open-login>Data Indikator</a>
                    </div></div>
                    <p class="sidebar-group-note">Kelola lewat login admin (unggah Excel)</p>
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
                <div class="brand-sub">Sistem Informasi Pemantauan Data — Penyakit Tidak Menular</div>
            </header>

            <main class="dashboard" id="top">

                <section class="toolbar-card" aria-label="Filter dashboard">
                    <div class="toolbar-title">Filter Dashboard</div>
                    <div class="toolbar-controls">
                        <label class="filter-control">
                            <span class="filter-label">Puskesmas</span>
                            <select id="puskesmasFilter" class="filter-select">
                                <option value="Semua">Semua Puskesmas</option>
                            </select>
                        </label>
                        <label class="filter-control">
                            <span class="filter-label">Periode</span>
                            <select id="periodeType" class="filter-select filter-select--small">
                                <option value="bulanan">Bulanan</option>
                                <option value="triwulan">Triwulan</option>
                                <option value="semester">Semester</option>
                                <option value="tahunan">Tahunan</option>
                            </select>
                        </label>
                        <label class="filter-control">
                            <span class="filter-label">Nilai Periode</span>
                            <select id="periodeValue" class="filter-select">
                                <option value="all">Pilih periode</option>
                            </select>
                        </label>
                    </div>
                </section>

                <section class="scoreboards" id="scoreboards">
                    <!-- 4 kartu scoreboard diisi via JS -->
                </section>

                <p class="dashboard-section-title">Alert Indikator Bermasalah</p>
                <section class="charts-row" style="grid-template-columns: 1fr;">
                    <div class="chart-card" id="alertPanelCard">
                        <h3>Perlu Perhatian (Belum Tercapai / Perlu Ditingkatkan)</h3>
                        <div class="alert-panel" id="alertPanel"></div>
                    </div>
                </section>

                <p class="dashboard-section-title">Progress Kabupaten</p>
                <section class="charts-row" style="grid-template-columns: 1fr;">
                    <div class="chart-card progress-kabupaten" id="progressKabupaten">
                        <div class="progress-kabupaten-label">
                            <span>Capaian terhadap Target Tahunan Kabupaten</span>
                            <strong id="progressKabupatenPercent">0%</strong>
                        </div>
                        <div class="progress-kabupaten-track">
                            <div class="progress-kabupaten-fill" id="progressKabupatenFill" style="width:0%"></div>
                        </div>
                        <div class="progress-kabupaten-label">
                            <span id="progressKabupatenNumbers">0 / 0 Orang</span>
                            <span id="progressKabupatenStatus"></span>
                        </div>
                    </div>
                </section>

                <p class="dashboard-section-title">Tren Capaian</p>
                <section class="charts-row" style="grid-template-columns: 1fr;">
                    <div class="chart-card">
                        <h3>Tren Rata-rata Capaian per Bulan</h3>
                        <div class="chart-canvas-box chart-canvas-box--short">
                            <canvas id="lineChart"></canvas>
                        </div>
                    </div>
                </section>

                <p class="dashboard-section-title">Target vs Capaian per Puskesmas</p>
                <section class="charts-row" style="grid-template-columns: 1fr;">
                    <div class="chart-card">
                        <div class="doughnut-header">
                            <h3>Target vs Capaian (Pasien)</h3>
                            <select id="comboFilter"></select>
                        </div>
                        <div class="chart-canvas-box chart-canvas-box--tall">
                            <canvas id="comboChart"></canvas>
                        </div>
                    </div>
                </section>

                <p class="dashboard-section-title">Distribusi Status</p>
                <section class="charts-row" style="grid-template-columns: 1fr;">
                    <div class="chart-card doughnut-card">
                        <div class="doughnut-header">
                            <h3>Distribusi Status</h3>
                            <select id="doughnutFilter"></select>
                        </div>
                        <div class="chart-canvas-box chart-canvas-box--short">
                            <canvas id="doughnutChart"></canvas>
                        </div>
                    </div>
                </section>

                <p class="dashboard-section-title">Ranking Indikator</p>
                <section class="charts-row" style="grid-template-columns: 1fr;">
                    <div class="chart-card">
                        <h3>Ranking Indikator (Capaian se-Kabupaten)</h3>
                        <div class="chart-canvas-box chart-canvas-box--short">
                            <canvas id="indikatorRankChart"></canvas>
                        </div>
                    </div>
                </section>

                <p class="dashboard-section-title">Ranking Puskesmas</p>
                <section class="charts-row" style="grid-template-columns: 1fr;">
                    <div class="chart-card">
                        <div class="doughnut-header">
                            <h3>Ranking Puskesmas (Skor Gabungan 4 Indikator)</h3>
                            <div class="rank-toggle" id="rankToggle">
                                <button type="button" data-mode="semua" class="is-active">Semua</button>
                                <button type="button" data-mode="top5">Top 5</button>
                                <button type="button" data-mode="bottom5">Bottom 5</button>
                            </div>
                        </div>
                        <div class="chart-canvas-box chart-canvas-box--tall">
                            <canvas id="barChart"></canvas>
                        </div>
                    </div>
                </section>

                <p class="dashboard-section-title">Heatmap Monitoring</p>
                <section class="charts-row" style="grid-template-columns: 1fr;">
                    <div class="chart-card table-card">
                        <div class="doughnut-header">
                            <h3>Puskesmas &times; Bulan</h3>
                            <select id="heatmapFilter"></select>
                        </div>
                        <div class="table-wrap heatmap-wrap">
                            <table id="heatmapTable">
                                <thead><tr id="heatmapHeadRow"></tr></thead>
                                <tbody id="heatmapBody"></tbody>
                            </table>
                        </div>
                        <div class="heatmap-legend">
                            <span><i style="background:#22c55e"></i> &ge;100% Tercapai</span>
                            <span><i style="background:#f59e0b"></i> 70&ndash;99% Perlu Ditingkatkan</span>
                            <span><i style="background:#ef4444"></i> &lt;70% Belum Tercapai</span>
                            <span><i style="background:#e5e7eb"></i> Tidak ada data</span>
                        </div>
                    </div>
                </section>

                <p class="dashboard-section-title">Detail per Puskesmas</p>
                <section class="table-section">
                    <div class="chart-card table-card">
                        <h3>Tabel Monitoring Puskesmas</h3>
                        <div class="search-field">
                            <svg class="search-field-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                            <input type="text" id="searchInput" placeholder="Cari puskesmas atau indikator...">
                        </div>
                        <div class="table-wrap">
                            <table id="monitorTable">
                                <thead>
                                    <tr>
                                        <th>Puskesmas</th><th>Indikator</th><th>Capaian/Target</th>
                                        <th>%</th><th>Status</th>
                                    </tr>
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

    <script src="assets/js/dashboard.js?v=<?= filemtime(__DIR__ . '/assets/js/dashboard.js') ?>"></script>
    <script src="assets/js/sidebar.js?v=<?= filemtime(__DIR__ . '/assets/js/sidebar.js') ?>"></script>
    <script src="assets/js/login-modal.js?v=<?= filemtime(__DIR__ . '/assets/js/login-modal.js') ?>"></script>
</body>
</html>