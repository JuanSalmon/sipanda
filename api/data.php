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
        'raw_rows' => [],
        'pesan' => $hasil['errors'][0] ?? 'Belum ada data. Admin perlu upload file Excel terlebih dahulu.',
    ]);
    exit;
}

// --- 1. SCOREBOARD: persentase gabungan (total capaian / total target) per indikator ---
// CATATAN: pakai persenGabungan() (total/total), BUKAN rata-rata kolom 'persentase' per baris.
// Rata-rata per baris rentan meledak kalau ada baris dengan TARGET BULANAN kecil
// (mis. target=4, capaian=10 -> 250% untuk baris itu saja), dan karena tak tertimbang,
// beberapa baris seperti itu bisa menyeret rata-rata keseluruhan jadi ribuan persen —
// ini penyebab bug "6412.6%" sebelumnya.
$scoreboard = [];
foreach (INDIKATOR_VALID as $ind) {
    $subset = array_values(array_filter($rows, fn($r) => $r['indikator'] === $ind));
    if (!$subset) continue;
    $scoreboard[] = [
        'indikator' => $ind,
        'rata_persen' => persenGabungan($subset),
        'total_capaian' => array_sum(array_column($subset, 'capaian')),
        'total_target' => array_sum(array_column($subset, 'target_bulanan')),
    ];
}

// --- 2. LINE CHART: tren persentase gabungan per bulan untuk 3 indikator ---
$lineIndikator = ['Usia Produktif', 'Hipertensi', 'Diabetes Mellitus'];
$lineChart = [];
foreach ($lineIndikator as $ind) {
    for ($b = 1; $b <= 6; $b++) {
        $subset = array_values(array_filter($rows, fn($r) => $r['indikator'] === $ind && $r['bulan'] === $b));
        if (!$subset) continue;
        $lineChart[] = [
            'indikator' => $ind,
            'bulan' => $b,
            'rata_persen' => persenGabungan($subset),
        ];
    }
}

// --- 3. BAR CHART: ranking puskesmas berdasarkan skor gabungan 4 indikator ---
// skor_gabungan = rata-rata dari 4 persentase gabungan (satu per indikator),
// supaya tiap indikator berbobot sama (bukan rata-rata 24 baris mentah).
$barChart = [];
foreach (PUSKESMAS_VALID as $pkm) {
    $skorPerIndikator = [];
    foreach (INDIKATOR_VALID as $ind) {
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
// kombinasi puskesmas+indikator selama Jan-Jun, bukan rata-rata baris.
$doughnut = ['Semua' => ['Tercapai'=>0, 'Perlu Ditingkatkan'=>0, 'Belum Tercapai'=>0]];
foreach (INDIKATOR_VALID as $ind) {
    $doughnut[$ind] = ['Tercapai'=>0, 'Perlu Ditingkatkan'=>0, 'Belum Tercapai'=>0];
    foreach (PUSKESMAS_VALID as $pkm) {
        $subset = array_values(array_filter($rows, fn($r) => $r['indikator'] === $ind && $r['puskesmas'] === $pkm));
        if (!$subset) continue;
        $rata = persenGabungan($subset);
        $status = hitungStatus($rata);
        $doughnut[$ind][$status]++;
        $doughnut['Semua'][$status]++;
    }
}

// --- 5. TABEL: monitoring semua puskesmas per indikator (persentase gabungan Jan-Jun) + status ---
$tabel = [];
foreach (PUSKESMAS_VALID as $pkm) {
    foreach (INDIKATOR_VALID as $ind) {
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

echo json_encode([
    'scoreboard' => $scoreboard,
    'line_chart' => $lineChart,
    'bar_chart' => $barChart,
    'doughnut' => $doughnut,
    'tabel' => $tabel,
    'raw_rows' => $rows,
]);