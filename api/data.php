<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/excel_reader.php';
require_once __DIR__ . '/../vendor/autoload.php';
header('Content-Type: application/json');

$hasil = bacaDataSipanda(EXCEL_DATA_PATH);
$rows = $hasil['rows'];

if (empty($rows)) {
    echo json_encode([
        'scoreboard' => [], 'line_chart' => [], 'bar_chart' => [],
        'doughnut' => ['Semua' => ['Tercapai'=>0,'Perlu Ditingkatkan'=>0,'Belum Tercapai'=>0]],
        'tabel' => [],
        'pesan' => $hasil['errors'][0] ?? 'Belum ada data. Admin perlu upload file Excel terlebih dahulu.',
    ]);
    exit;
}

// --- 1. SCOREBOARD: rata-rata persentase per indikator + total capaian/target ---
$scoreboard = [];
foreach (INDIKATOR_VALID as $ind) {
    $subset = array_filter($rows, fn($r) => $r['indikator'] === $ind);
    if (!$subset) continue;
    $scoreboard[] = [
        'indikator' => $ind,
        'rata_persen' => round(array_sum(array_column($subset, 'persentase')) / count($subset), 1),
        'total_capaian' => array_sum(array_column($subset, 'capaian')),
        'total_target' => array_sum(array_column($subset, 'target_bulanan')),
    ];
}

// --- 2. LINE CHART: tren rata-rata % per bulan untuk 3 indikator ---
$lineIndikator = ['Usia Produktif', 'Hipertensi', 'Diabetes Mellitus'];
$lineChart = [];
foreach ($lineIndikator as $ind) {
    for ($b = 1; $b <= 6; $b++) {
        $subset = array_filter($rows, fn($r) => $r['indikator'] === $ind && $r['bulan'] === $b);
        if (!$subset) continue;
        $lineChart[] = [
            'indikator' => $ind,
            'bulan' => $b,
            'rata_persen' => round(array_sum(array_column($subset, 'persentase')) / count($subset), 1),
        ];
    }
}

// --- 3. BAR CHART: ranking puskesmas berdasarkan skor gabungan 4 indikator ---
$barChart = [];
foreach (PUSKESMAS_VALID as $pkm) {
    $subset = array_filter($rows, fn($r) => $r['puskesmas'] === $pkm);
    if (!$subset) continue;
    $barChart[] = [
        'puskesmas' => $pkm,
        'skor_gabungan' => round(array_sum(array_column($subset, 'persentase')) / count($subset), 1),
    ];
}
usort($barChart, fn($a, $b) => $b['skor_gabungan'] <=> $a['skor_gabungan']);

// --- 4. DOUGHNUT: jumlah puskesmas per status, keseluruhan + per indikator ---
// dihitung dari rata-rata % tiap kombinasi puskesmas+indikator
$doughnut = ['Semua' => ['Tercapai'=>0, 'Perlu Ditingkatkan'=>0, 'Belum Tercapai'=>0]];
foreach (INDIKATOR_VALID as $ind) {
    $doughnut[$ind] = ['Tercapai'=>0, 'Perlu Ditingkatkan'=>0, 'Belum Tercapai'=>0];
    foreach (PUSKESMAS_VALID as $pkm) {
        $subset = array_filter($rows, fn($r) => $r['indikator'] === $ind && $r['puskesmas'] === $pkm);
        if (!$subset) continue;
        $rata = array_sum(array_column($subset, 'persentase')) / count($subset);
        $status = hitungStatus($rata);
        $doughnut[$ind][$status]++;
        $doughnut['Semua'][$status]++;
    }
}

// --- 5. TABEL: monitoring semua puskesmas per indikator (rata-rata Jan-Jun) + status ---
$tabel = [];
foreach (PUSKESMAS_VALID as $pkm) {
    foreach (INDIKATOR_VALID as $ind) {
        $subset = array_values(array_filter($rows, fn($r) => $r['puskesmas'] === $pkm && $r['indikator'] === $ind));
        if (!$subset) continue;
        $rata = round(array_sum(array_column($subset, 'persentase')) / count($subset), 1);
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

echo json_encode([
    'scoreboard' => $scoreboard,
    'line_chart' => $lineChart,
    'bar_chart' => $barChart,
    'doughnut' => $doughnut,
    'tabel' => $tabel,
]);
