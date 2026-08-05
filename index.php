<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SIPANDA PTM - Dashboard Monitoring</title>
<link rel="stylesheet" href="assets/css/style.css?v=<?= filemtime(__DIR__ . '/assets/css/style.css') ?>">
<link rel="stylesheet" href="assets/css/sidebar.css?v=<?= filemtime(__DIR__ . '/assets/css/sidebar.css') ?>">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
</head>
<body>
    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

    <div class="app-shell">
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-brand">
                <div class="sidebar-brand-mark">S</div>
                <div>
                    <div class="sidebar-brand-title">SIPANDA <span>PTM</span></div>
                    <div class="sidebar-brand-sub">Sistem Informasi Pemantauan Data PTM</div>
                </div>
            </div>

            <nav class="sidebar-nav">
                <a class="sidebar-link is-active" href="#top" data-target="top">
                    <span class="sidebar-icon">🏠</span> Dashboard
                </a>

                <div class="sidebar-group is-expanded">
                    <button class="sidebar-group-toggle" type="button" aria-expanded="true">
                        <span class="sidebar-icon">📊</span> Monitoring <span class="sidebar-caret">›</span>
                    </button>
                    <div class="sidebar-submenu"><div>
                        <a href="#scoreboards" data-target="scoreboards">Ringkasan Capaian</a>
                        <a href="#monitorTable" data-target="monitorTable">Monitoring per Puskesmas</a>
                        <a href="#gaugeRow" data-target="gaugeRow">Monitoring per Indikator</a>
                        <a href="#heatmapTable" data-target="heatmapTable">Monitoring Bulanan</a>
                    </div></div>
                </div>

                <div class="sidebar-group">
                    <button class="sidebar-group-toggle" type="button" aria-expanded="false">
                        <span class="sidebar-icon">📈</span> Analisis <span class="sidebar-caret">›</span>
                    </button>
                    <div class="sidebar-submenu"><div>
                        <a href="#lineChart" data-target="lineChart">Tren Capaian</a>
                        <a href="#comboChart" data-target="comboChart">Target vs Capaian</a>
                        <a href="#barChart" data-target="barChart">Ranking Puskesmas</a>
                        <a href="#doughnutChart" data-target="doughnutChart">Distribusi Status</a>
                    </div></div>
                </div>

                <div class="sidebar-group">
                    <button class="sidebar-group-toggle" type="button" aria-expanded="false">
                        <span class="sidebar-icon">📋</span> Data PTM <span class="sidebar-caret">›</span>
                    </button>
                    <div class="sidebar-submenu"><div>
                        <a href="admin/login.php">Data Capaian</a>
                        <a href="admin/login.php">Data Target</a>
                        <a href="admin/login.php">Data Puskesmas</a>
                        <a href="admin/login.php">Data Indikator</a>
                    </div></div>
                    <p class="sidebar-group-note">Kelola lewat login admin (unggah Excel)</p>
                </div>

                <a class="sidebar-link sidebar-link--disabled" href="#" aria-disabled="true" onclick="return false;">
                    <span class="sidebar-icon">📑</span> Laporan <span class="sidebar-soon">Segera hadir</span>
                </a>

                <a class="sidebar-link sidebar-link--disabled" href="#" aria-disabled="true" onclick="return false;">
                    <span class="sidebar-icon">⚙️</span> Pengaturan <span class="sidebar-soon">Segera hadir</span>
                </a>
            </nav>

            <div class="sidebar-footer">
                <a class="sidebar-footer-link" href="admin/login.php">
                    <span class="sidebar-icon">🔐</span> Login Admin
                </a>
            </div>
        </aside>

        <div class="app-main">
            <header class="topbar">
                <button class="sidebar-toggle" id="sidebarToggle" type="button" aria-label="Buka menu" aria-expanded="false">☰</button>
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

                <p class="dashboard-section-title">Progress per Indikator</p>
                <section class="gauge-row" id="gaugeRow">
                    <!-- kartu gauge per indikator diisi via JS -->
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
                        <div class="chart-canvas-box chart-canvas-box--short">
                            <canvas id="comboChart"></canvas>
                        </div>
                    </div>
                </section>

                <p class="dashboard-section-title">Distribusi &amp; Ranking</p>
                <section class="charts-row" style="grid-template-columns: 1fr 1.4fr;">
                    <div class="chart-card doughnut-card">
                        <div class="doughnut-header">
                            <h3>Distribusi Status</h3>
                            <select id="doughnutFilter"></select>
                        </div>
                        <canvas id="doughnutChart"></canvas>
                    </div>
                    <div class="chart-card">
                        <h3>Ranking Puskesmas (Skor Gabungan 4 Indikator)</h3>
                        <canvas id="barChart"></canvas>
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
                        <input type="text" id="searchInput" placeholder="Cari puskesmas atau indikator...">
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

    <script src="assets/js/dashboard.js?v=<?= filemtime(__DIR__ . '/assets/js/dashboard.js') ?>"></script>
    <script src="assets/js/sidebar.js?v=<?= filemtime(__DIR__ . '/assets/js/sidebar.js') ?>"></script>
</body>
</html>