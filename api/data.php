<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/excel_reader.php';
require_once __DIR__ . '/../vendor/autoload.php';
header('Content-Type: application/json');

// === Cache layer ===
// Tujuan: pindah dashboard kabupaten <-> puskesmas terasa instant.
// Invalidasi: otomatis. Cache valid hanya bila file Excel belum dimodifikasi
// sejak cache ditulis. Upload Excel baru akan update mtime, cache otomatis invalid.
// Folder cache sengaja berada di LUAR webroot uploads/ supaya tidak ikut ke-serve
// Apache. Letakkan di tmp path yang tidak publik.
$cacheDir = __DIR__ . '/../cache';
$cachePath = $cacheDir . '/data_' . md5(EXCEL_DATA_PATH) . '.json';

if (!is_dir($cacheDir)) {
    @mkdir($cacheDir, 0755, true);
}

$sourceMtime = is_file(EXCEL_DATA_PATH) ? filemtime(EXCEL_DATA_PATH) : 0;
$cacheMtime  = is_file($cachePath) ? filemtime($cachePath) : 0;

if ($sourceMtime > 0 && $cacheMtime >= $sourceMtime) {
    // Cache HIT - kirim JSON yang sudah jadi tanpa parse Excel.
    header('X-Cache: HIT');
    readfile($cachePath);
    exit;
}

// === Cache MISS: parse Excel & hitung agregasi ===
$hasil = bacaDataSipanda(EXCEL_DATA_PATH);
$rows = $hasil['rows'];

if (empty($rows)) {
    $payload = json_encode([
        'scoreboard' => [], 'line_chart' => [], 'bar_chart' => [],
        'doughnut' => ['Semua' => ['Tercapai'=>0,'Perlu Ditingkatkan'=>0,'Belum Tercapai'=>0]],
        'tabel' => [],
        'raw_rows' => [],
        'pesan' => $hasil['errors'][0] ?? 'Belum ada data. Admin perlu upload file Excel terlebih dahulu.',
    ]);
    header('X-Cache: MISS');
    echo $payload;
    exit;
}

// --- 1. SCOREBOARD: persentase gabungan (total capaian / total target) per indikator ---
// CATATAN: pakai persenGabungan() (total/total), BUKAN rata-rata kolom 'persentase' per baris.
// Rata-rata per baris rentan meledak kalau ada baris dengan TARGET BULANAN kecil
// (mis. target=4, capaian=10 -> 250% untuk baris itu saja), dan karena tak tertimbang,
// beberapa baris seperti itu bisa menyeret rata-rata keseluruhan jadi ribuan persen —
// ini penyebab bug "6412.6%" sebelumnya.
$indicatorList = array_values(array_unique(array_map(fn($r) => $r['indikator'], $rows)));
sort($indicatorList, SORT_NATURAL | SORT_FLAG_CASE);

$scoreboard = [];
foreach ($indicatorList as $ind) {
    $subset = array_values(array_filter($rows, fn($r) => $r['indikator'] === $ind));
    if (!$subset) continue;
    $scoreboard[] = [
        'indikator' => $ind,
        'rata_persen' => persenGabungan($subset),
        'total_capaian' => array_sum(array_column($subset, 'capaian')),
        'total_target' => array_sum(array_column($subset, 'target_bulanan')),
    ];
}

// --- 2. LINE CHART: tren persentase gabungan per bulan untuk indikator yang tersedia di file baru ---
$lineChart = [];
foreach ($indicatorList as $ind) {
    $months = array_values(array_unique(array_map(fn($r) => (int)$r['bulan'], array_filter($rows, fn($r) => $r['indikator'] === $ind))));
    sort($months, SORT_NUMERIC);
    foreach ($months as $b) {
        $subset = array_values(array_filter($rows, fn($r) => $r['indikator'] === $ind && (int)$r['bulan'] === $b));
        if (!$subset) continue;
        $lineChart[] = [
            'indikator' => $ind,
            'bulan' => $b,
            'rata_persen' => persenGabungan($subset),
        ];
    }
}

// --- 3. BAR CHART: ranking puskesmas berdasarkan skor gabungan indikator yang tersedia ---
// skor_gabungan = rata-rata dari persentase gabungan tiap indikator,
// supaya tiap indikator berbobot sama (bukan rata-rata 24 baris mentah).
$puskesmasList = array_values(array_unique(array_map(fn($r) => $r['puskesmas'], $rows)));
sort($puskesmasList, SORT_NATURAL | SORT_FLAG_CASE);

$barChart = [];
foreach ($puskesmasList as $pkm) {
    $skorPerIndikator = [];
    foreach ($indicatorList as $ind) {
        $subset = array_values(array_filter($rows, fn($r) => $r['puskesmas'] === $pkm && $r['indikator'] === $ind));
        if (!$subset) continue;
        $skorPerIndikator[] = persenGabungan($subset);
    }
    if (!$skorPerIndikator) continue;
    $barChart[] = [
        'puskesmas' => $pkm,
        'skor_gabungan' => round(array_sum($skorPerIndikator) / count($skorPerIndikator), 1),
    ];
}
usort($barChart, fn($a, $b) => $b['skor_gabungan'] <=> $a['skor_gabungan']);

// --- 4. DOUGHNUT: jumlah puskesmas per status, keseluruhan + per indikator ---
// status dihitung dari persentase gabungan (total capaian/total target) tiap
// kombinasi puskesmas+indikator, bukan rata-rata baris.
$doughnut = ['Semua' => ['Tercapai'=>0, 'Perlu Ditingkatkan'=>0, 'Belum Tercapai'=>0]];
foreach ($indicatorList as $ind) {
    $doughnut[$ind] = ['Tercapai'=>0, 'Perlu Ditingkatkan'=>0, 'Belum Tercapai'=>0];
    foreach ($puskesmasList as $pkm) {
        $subset = array_values(array_filter($rows, fn($r) => $r['indikator'] === $ind && $r['puskesmas'] === $pkm));
        if (!$subset) continue;
        $rata = persenGabungan($subset);
        $status = hitungStatus($rata);
        $doughnut[$ind][$status]++;
        $doughnut['Semua'][$status]++;
    }
}

// --- 5. TABEL: monitoring semua puskesmas per indikator (persentase gabungan) + status ---
$tabel = [];
foreach ($puskesmasList as $pkm) {
    foreach ($indicatorList as $ind) {
        $subset = array_values(array_filter($rows, fn($r) => $r['puskesmas'] === $pkm && $r['indikator'] === $ind));
        if (!$subset) continue;
        $rata = persenGabungan($subset);
        $tabel[] = [
            'puskesmas' => $pkm,
            'indikator' => $ind,
            'rata_persen' => $rata,
            'total_capaian' => array_sum(array_column($subset, 'capaian')),
            'total_target' => array_sum(array_column($subset, 'target_bulanan')),
            'status' => hitungStatus($rata),
        ];
    }
}

$payload = json_encode([
    'scoreboard' => $scoreboard,
    'line_chart' => $lineChart,
    'bar_chart' => $barChart,
    'doughnut' => $doughnut,
    'tabel' => $tabel,
    'raw_rows' => $rows,
]);

// Simpan ke cache. atomic write: tulis ke .tmp lalu rename, supaya request
// paralel tidak baca file setengah jadi.
if ($sourceMtime > 0) {
    $tmp = $cachePath . '.tmp.' . getmypid();
    if (@file_put_contents($tmp, $payload) !== false) {
        @rename($tmp, $cachePath);
        @touch($cachePath, $sourceMtime); // sync mtime dengan source, biar compare berikutnya valid
    }
}

header('X-Cache: MISS');
echo $payload;