<?php
// SIPANDA PTM - Baca data langsung dari file Excel, tidak disimpan ke database.
require_once __DIR__ . '/functions.php';

const PUSKESMAS_VALID = ['Baa','Batutua','Busalangga','Delha','Eahun','Feapopi',
    'Korbafo','Ndao','Oelaba','Oele','Sonimanu','Sotimori'];
const INDIKATOR_VALID = ['Usia Produktif','Hipertensi','Diabetes Mellitus','HPV DNA'];

const BULAN_MAP = [
    'JAN'=>1,'JANUARI'=>1,'FEB'=>2,'FEBRUARI'=>2,'MAR'=>3,'MARET'=>3,
    'APR'=>4,'APRIL'=>4,'MEI'=>5,'MAY'=>5,'JUN'=>6,'JUNI'=>6,
    'JUL'=>7,'JULI'=>7,'AGU'=>8,'AGUSTUS'=>8,'SEP'=>9,'SEPTEMBER'=>9,
    'OKT'=>10,'OKTOBER'=>10,'NOV'=>11,'NOVEMBER'=>11,'DES'=>12,'DESEMBER'=>12,
];

/**
 * Baca file Excel sheet "DATABASE SIPANDA" dan kembalikan array baris yang sudah bersih
 * (persentase & status dihitung ulang, bukan dari file), plus daftar baris error.
 *
 * @return array{rows: array, errors: array, total_baris_dibaca: int}
 */
function bacaDataSipanda(string $path): array {
    if (!file_exists($path)) {
        return ['rows' => [], 'errors' => ['File data belum diupload.'], 'total_baris_dibaca' => 0];
    }

    $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($path);
    if (!$spreadsheet->sheetNameExists('DATABASE SIPANDA')) {
        return ['rows' => [], 'errors' => ['Sheet "DATABASE SIPANDA" tidak ditemukan di file.'], 'total_baris_dibaca' => 0];
    }

    $sheet = $spreadsheet->getSheetByName('DATABASE SIPANDA');
    $data = $sheet->toArray(null, true, true, false);

    $header = array_map(fn($h) => strtoupper(trim((string)$h)), $data[0] ?? []);
    $col = fn($name) => array_search($name, $header);

    $idxTahun     = $col('TAHUN');
    $idxNoBulan   = $col('NO BULAN');
    $idxBulan     = $col('BULAN');
    $idxPuskesmas = $col('PUSKESMAS');
    $idxIndikator = $col('INDIKATOR');
    $idxSasaran   = $col('SASARAN');
    $idxTargetThn = $col('TARGET TAHUNAN');
    $idxTargetBln = $col('TARGET BULANAN');
    $idxCapaian   = $col('CAPAIAN');

    $wajib = compact('idxPuskesmas','idxIndikator','idxSasaran','idxTargetThn','idxTargetBln','idxCapaian');
    foreach ($wajib as $key => $val) {
        if ($val === false) {
            return ['rows' => [], 'errors' => ["Kolom wajib tidak ditemukan: $key"], 'total_baris_dibaca' => 0];
        }
    }
    if ($idxNoBulan === false && $idxBulan === false) {
        return ['rows' => [], 'errors' => ['Kolom wajib tidak ditemukan: BULAN atau NO BULAN'], 'total_baris_dibaca' => 0];
    }

    $puskesmasSet = array_flip(array_map('strtoupper', PUSKESMAS_VALID));
    $indikatorSet = array_flip(array_map('strtoupper', INDIKATOR_VALID));

    $rows = [];
    $errors = [];

    for ($i = 1; $i < count($data); $i++) {
        $r = $data[$i];
        $namaPuskesmas = trim((string)($r[$idxPuskesmas] ?? ''));
        $namaIndikator = trim((string)($r[$idxIndikator] ?? ''));
        if ($namaPuskesmas === '' || $namaIndikator === '') continue; // baris kosong

        if (!isset($puskesmasSet[strtoupper($namaPuskesmas)])) {
            $errors[] = "Baris " . ($i+1) . ": Puskesmas '$namaPuskesmas' tidak dikenali";
            continue;
        }
        if (!isset($indikatorSet[strtoupper($namaIndikator)])) {
            $errors[] = "Baris " . ($i+1) . ": Indikator '$namaIndikator' tidak dikenali";
            continue;
        }

        $bulan = null;
        if ($idxNoBulan !== false) {
            $v = (int)($r[$idxNoBulan] ?? 0);
            if ($v >= 1 && $v <= 12) $bulan = $v;
        }
        if ($bulan === null && $idxBulan !== false) {
            $bulan = BULAN_MAP[strtoupper(trim((string)($r[$idxBulan] ?? '')))] ?? null;
        }
        if ($bulan === null) {
            $errors[] = "Baris " . ($i+1) . ": Bulan tidak dikenali";
            continue;
        }

        $targetBln = (int)($r[$idxTargetBln] ?? 0);
        $capaian   = (int)($r[$idxCapaian] ?? 0);
        $persentase = $targetBln > 0 ? round(($capaian / $targetBln) * 100, 2) : 0;

        $rows[] = [
            'tahun'          => $idxTahun !== false ? (int)($r[$idxTahun] ?? 2026) : 2026,
            'bulan'          => $bulan,
            'puskesmas'      => $namaPuskesmas,
            'indikator'      => $namaIndikator,
            'sasaran'        => (int)($r[$idxSasaran] ?? 0),
            'target_tahunan' => (int)($r[$idxTargetThn] ?? 0),
            'target_bulanan' => $targetBln,
            'capaian'        => $capaian,
            'persentase'     => $persentase,
            'status'         => hitungStatus($persentase),
        ];
    }

    return ['rows' => $rows, 'errors' => $errors, 'total_baris_dibaca' => count($data) - 1];
}
