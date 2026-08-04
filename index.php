<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SIPANDA PTM - Dashboard Monitoring</title>
<link rel="stylesheet" href="assets/css/style.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
</head>
<body>
    <header class="topbar">
        <div class="brand">SIPANDA <span>PTM</span></div>
        <div class="brand-sub">Sistem Informasi Pemantauan Data — Penyakit Tidak Menular</div>
        <a class="admin-link" href="admin/login.php">Login Admin</a>
    </header>

    <main class="dashboard">

        <section class="scoreboards" id="scoreboards">
            <!-- 4 kartu scoreboard diisi via JS -->
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

    <script src="assets/js/dashboard.js"></script>
</body>
</html>