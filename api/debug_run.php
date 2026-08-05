<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/excel_reader.php';
require_once __DIR__ . '/../vendor/autoload.php';

$hasil = bacaDataSipanda(EXCEL_DATA_PATH);
$rows  = $hasil['rows'];

echo 'Total baris: ' . count($rows) . PHP_EOL;
echo 'Sheets dibaca: ' . implode(', ', $hasil['sheets_dibaca']) . PHP_EOL;
echo 'Errors: ' . count($hasil['errors']) . PHP_EOL;
echo PHP_EOL;

$summary = [];
foreach ($rows as $r) {
    $ind = $r['indikator'];
    if (!isset($summary[$ind])) {
        $summary[$ind] = [
            'count'            => 0,
            'total_capaian'    => 0,
            'total_target_bln' => 0,
            'min_target'       => PHP_FLOAT_MAX,
            'max_target'       => 0,
            'contoh'           => [],
        ];
    }
    $summary[$ind]['count']++;
    $summary[$ind]['total_capaian']    += $r['capaian'];
    $summary[$ind]['total_target_bln'] += $r['target_bulanan'];
    $summary[$ind]['min_target'] = min($summary[$ind]['min_target'], $r['target_bulanan']);
    $summary[$ind]['max_target'] = max($summary[$ind]['max_target'], $r['target_bulanan']);
    if (count($summary[$ind]['contoh']) < 3) {
        $summary[$ind]['contoh'][] = [
            'puskesmas'      => $r['puskesmas'],
            'bulan'          => $r['bulan'],
            'target_tahunan' => $r['target_tahunan'],
            'target_bulanan' => $r['target_bulanan'],
            'capaian'        => $r['capaian'],
            'persentase'     => $r['persentase'],
        ];
    }
}

foreach ($summary as $ind => $s) {
    $pg = $s['total_target_bln'] > 0
        ? round(($s['total_capaian'] / $s['total_target_bln']) * 100, 1)
        : 0;

    echo '=== ' . $ind . ' ===' . PHP_EOL;
    echo '  Jumlah baris      : ' . $s['count'] . PHP_EOL;
    echo '  Total capaian     : ' . $s['total_capaian'] . PHP_EOL;
    echo '  Total target_bln  : ' . $s['total_target_bln'] . PHP_EOL;
    echo '  Min target_bln    : ' . $s['min_target'] . PHP_EOL;
    echo '  Max target_bln    : ' . $s['max_target'] . PHP_EOL;
    echo '  Persen gabungan   : ' . $pg . '%' . PHP_EOL;
    echo '  Contoh baris:' . PHP_EOL;
    foreach ($s['contoh'] as $c) {
        echo '    pkm=' . $c['puskesmas']
            . ' bln=' . $c['bulan']
            . ' tgt_thn=' . $c['target_tahunan']
            . ' tgt_bln=' . $c['target_bulanan']
            . ' capaian=' . $c['capaian']
            . ' persen=' . $c['persentase'] . '%'
            . PHP_EOL;
    }
    echo PHP_EOL;
}
