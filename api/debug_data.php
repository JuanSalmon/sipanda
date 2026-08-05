<?php
// SIPANDA PTM - Debug endpoint (HAPUS setelah debugging selesai)
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/excel_reader.php';
require_once __DIR__ . '/../vendor/autoload.php';
header('Content-Type: application/json');

$hasil = bacaDataSipanda(EXCEL_DATA_PATH);
$rows  = $hasil['rows'];

// Rangkum per indikator: tampilkan min/max/sum target_bulanan, capaian, dan persentase
$summary = [];
foreach ($rows as $r) {
    $ind = $r['indikator'];
    if (!isset($summary[$ind])) {
        $summary[$ind] = [
            'count'            => 0,
            'total_capaian'    => 0,
            'total_target_bln' => 0,
            'min_target_bln'   => PHP_FLOAT_MAX,
            'max_target_bln'   => 0,
            'min_capaian'      => PHP_FLOAT_MAX,
            'max_capaian'      => 0,
            'min_persen_baris' => PHP_FLOAT_MAX,
            'max_persen_baris' => 0,
            'contoh_baris'     => [],
        ];
    }
    $s = &$summary[$ind];
    $s['count']++;
    $s['total_capaian']    += $r['capaian'];
    $s['total_target_bln'] += $r['target_bulanan'];
    $s['min_target_bln']    = min($s['min_target_bln'], $r['target_bulanan']);
    $s['max_target_bln']    = max($s['max_target_bln'], $r['target_bulanan']);
    $s['min_capaian']       = min($s['min_capaian'], $r['capaian']);
    $s['max_capaian']       = max($s['max_capaian'], $r['capaian']);
    $s['min_persen_baris']  = min($s['min_persen_baris'], $r['persentase']);
    $s['max_persen_baris']  = max($s['max_persen_baris'], $r['persentase']);
    // Ambil maks 5 contoh baris per indikator
    if (count($s['contoh_baris']) < 5) {
        $s['contoh_baris'][] = [
            'puskesmas'      => $r['puskesmas'],
            'bulan'          => $r['bulan'],
            'sasaran'        => $r['sasaran'],
            'target_tahunan' => $r['target_tahunan'],
            'target_bulanan' => $r['target_bulanan'],
            'capaian'        => $r['capaian'],
            'persentase'     => $r['persentase'],
        ];
    }
}

// Hitung persentase gabungan (cara yang benar)
foreach ($summary as $ind => &$s) {
    $s['persen_gabungan_benar'] = $s['total_target_bln'] > 0
        ? round(($s['total_capaian'] / $s['total_target_bln']) * 100, 1)
        : 0;
    if ($s['min_target_bln'] === PHP_FLOAT_MAX) $s['min_target_bln'] = 0;
    if ($s['min_capaian']    === PHP_FLOAT_MAX) $s['min_capaian']    = 0;
    if ($s['min_persen_baris'] === PHP_FLOAT_MAX) $s['min_persen_baris'] = 0;
}

echo json_encode([
    'total_baris'   => count($rows),
    'errors'        => $hasil['errors'],
    'sheets_dibaca' => $hasil['sheets_dibaca'],
    'ringkasan_per_indikator' => $summary,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
