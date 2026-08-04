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
 * Cari sheet-sheet di workbook yang berisi struktur data SIPANDA.
 * Jika ada sheet bernama DATABASE SIPANDA, dia akan diprioritaskan.
 *
 * @return array<int, array{name: string, data: array}>
 */
function cariSheetDataSipanda(\PhpOffice\PhpSpreadsheet\Spreadsheet $spreadsheet): array {
    $candidates = [];
    foreach ($spreadsheet->getSheetNames() as $sheetName) {
        $sheet = $spreadsheet->getSheetByName($sheetName);
        $data = $sheet->toArray(null, true, true, false);
        if (count($data) < 2) {
            continue;
        }

        $header = array_map(fn($h) => strtoupper(trim((string)$h)), $data[0] ?? []);
        $headerSet = array_flip($header);

        $required = ['TAHUN', 'NO BULAN', 'BULAN', 'PUSKESMAS', 'INDIKATOR', 'SASARAN', 'TARGET TAHUNAN', 'TARGET BULANAN', 'CAPAIAN'];
        $hasRequired = true;
        foreach ($required as $field) {
            if (!isset($headerSet[$field])) {
                $hasRequired = false;
                break;
            }
        }

        if ($hasRequired) {
            $candidates[] = ['name' => $sheetName, 'data' => $data];
        }
    }

    usort($candidates, function (array $a, array $b): int {
        if ($a['name'] === 'DATABASE SIPANDA') {
            return -1;
        }
        if ($b['name'] === 'DATABASE SIPANDA') {
            return 1;
        }
        return strcmp($a['name'], $b['name']);
    });

    return $candidates;
}

/**
 * Baca file Excel workbook dan ambil baris data dari sheet yang relevan.
 * Dengan struktur workbook baru, sistem sekarang bisa men-scan beberapa sheet
 * dan memakai sheet data utama yang memiliki kolom SIPANDA.
 *
 * @return array{rows: array, errors: array, total_baris_dibaca: int, sheets_dibaca: array}
 */
function bacaDataSipanda(string $path): array {
    if (!file_exists($path)) {
        return ['rows' => [], 'errors' => ['File data belum diupload.'], 'total_baris_dibaca' => 0, 'sheets_dibaca' => []];
    }

    $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($path);
    $candidates = cariSheetDataSipanda($spreadsheet);

    if ($candidates === []) {
        return [
            'rows' => [],
            'errors' => ['Tidak ada sheet data yang cocok. Sheet yang diperlukan harus memiliki kolom: TAHUN, NO BULAN/BULAN, PUSKESMAS, INDIKATOR, SASARAN, TARGET TAHUNAN, TARGET BULANAN, CAPAIAN.'],
            'total_baris_dibaca' => 0,
            'sheets_dibaca' => $spreadsheet->getSheetNames(),
        ];
    }

    $puskesmasSet = array_flip(array_map('strtoupper', PUSKESMAS_VALID));
    $indikatorSet = array_flip(array_map('strtoupper', INDIKATOR_VALID));

    $rows = [];
    $errors = [];
    $totalBarisDibaca = 0;

    foreach ($candidates as $candidate) {
        $data = $candidate['data'];
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
                $errors[] = "Sheet {$candidate['name']}: kolom wajib tidak ditemukan: $key";
                continue 2;
            }
        }
        if ($idxNoBulan === false && $idxBulan === false) {
            $errors[] = "Sheet {$candidate['name']}: kolom wajib tidak ditemukan: BULAN atau NO BULAN";
            continue;
        }

        $totalBarisDibaca += count($data) - 1;

        for ($i = 1; $i < count($data); $i++) {
            $r = $data[$i];
            $namaPuskesmas = trim((string)($r[$idxPuskesmas] ?? ''));
            $namaIndikator = trim((string)($r[$idxIndikator] ?? ''));
            if ($namaPuskesmas === '' || $namaIndikator === '') continue; // baris kosong

            if (!isset($puskesmasSet[strtoupper($namaPuskesmas)])) {
                $errors[] = "Sheet {$candidate['name']}, Baris " . ($i+1) . ": Puskesmas '$namaPuskesmas' tidak dikenali";
                continue;
            }
            if (!isset($indikatorSet[strtoupper($namaIndikator)])) {
                $errors[] = "Sheet {$candidate['name']}, Baris " . ($i+1) . ": Indikator '$namaIndikator' tidak dikenali";
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
                $errors[] = "Sheet {$candidate['name']}, Baris " . ($i+1) . ": Bulan tidak dikenali";
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
    }

    return [
        'rows' => $rows,
        'errors' => $errors,
        'total_baris_dibaca' => $totalBarisDibaca,
        'sheets_dibaca' => array_map(fn($candidate) => $candidate['name'], $candidates),
    ];
}
