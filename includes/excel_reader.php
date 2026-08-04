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

function normalizeHeaderName(string $value): string {
    return strtoupper(trim(preg_replace('/\s+/', ' ', (string)$value)));
}

function detectSheetKind(array $headerSet, string $sheetName): ?string {
    $isDatabaseSheet = isset($headerSet['TAHUN'])
        && isset($headerSet['PUSKESMAS'])
        && isset($headerSet['INDIKATOR'])
        && isset($headerSet['TARGET BULANAN'])
        && isset($headerSet['CAPAIAN']);

    if ($isDatabaseSheet) {
        return 'database';
    }

    $isTargetSheet = isset($headerSet['TAHUN'])
        && isset($headerSet['PUSKESMAS'])
        && isset($headerSet['INDIKATOR'])
        && (isset($headerSet['TARGET']) || isset($headerSet['TARGET BULANAN']) || isset($headerSet['TARGET TAHUNAN']));

    if ($isTargetSheet) {
        return 'target';
    }

    $isRealisasiSheet = isset($headerSet['TAHUN'])
        && isset($headerSet['PUSKESMAS'])
        && isset($headerSet['INDIKATOR'])
        && (isset($headerSet['CAPAIAN']) || isset($headerSet['CAPAIAN BULANAN']));

    if ($isRealisasiSheet) {
        return 'realisasi';
    }

    return null;
}

function getHeaderIndex(array $header, array $names): int|false {
    foreach ($names as $name) {
        $idx = array_search($name, $header, true);
        if ($idx !== false) {
            return $idx;
        }
    }

    return false;
}

function parseBulan(array $row, int|false $idxNoBulan, int|false $idxBulan): ?int {
    if ($idxNoBulan !== false) {
        $value = (int)($row[$idxNoBulan] ?? 0);
        if ($value >= 1 && $value <= 12) {
            return $value;
        }
    }

    if ($idxBulan !== false) {
        $rawBulan = strtoupper(trim((string)($row[$idxBulan] ?? '')));
        return BULAN_MAP[$rawBulan] ?? null;
    }

    return null;
}

function buildRowFromSheetData(array $sheetRow, array $header, string $sheetName, int $rowNumber, string $kind): ?array {
    $idxTahun = getHeaderIndex($header, ['TAHUN']);
    $idxNoBulan = getHeaderIndex($header, ['NO BULAN', 'NO. BULAN']);
    $idxBulan = getHeaderIndex($header, ['BULAN']);
    $idxPuskesmas = getHeaderIndex($header, ['PUSKESMAS']);
    $idxIndikator = getHeaderIndex($header, ['INDIKATOR']);
    $idxSasaran = getHeaderIndex($header, ['SASARAN']);
    $idxTargetThn = getHeaderIndex($header, ['TARGET TAHUNAN']);
    $idxTargetBln = getHeaderIndex($header, ['TARGET BULANAN', 'TARGET']);
    $idxCapaian = getHeaderIndex($header, ['CAPAIAN', 'CAPAIAN BULANAN']);

    if ($idxPuskesmas === false || $idxIndikator === false) {
        return null;
    }

    $namaPuskesmas = trim((string)($sheetRow[$idxPuskesmas] ?? ''));
    $namaIndikator = trim((string)($sheetRow[$idxIndikator] ?? ''));
    if ($namaPuskesmas === '' || $namaIndikator === '') {
        return null;
    }

    $bulan = parseBulan($sheetRow, $idxNoBulan, $idxBulan);
    if ($bulan === null) {
        return null;
    }

    $targetBln = 0.0;
    if ($idxTargetBln !== false) {
        $targetBln = (float)($sheetRow[$idxTargetBln] ?? 0);
    }

    $capaian = 0.0;
    if ($idxCapaian !== false) {
        $capaian = (float)($sheetRow[$idxCapaian] ?? 0);
    }

    if ($kind === 'realisasi') {
        $targetBln = 0.0;
    }

    $persentase = $targetBln > 0 ? round(($capaian / $targetBln) * 100, 2) : 0;

    return [
        'tahun' => $idxTahun !== false ? (int)($sheetRow[$idxTahun] ?? 2026) : 2026,
        'bulan' => $bulan,
        'puskesmas' => $namaPuskesmas,
        'indikator' => $namaIndikator,
        'sasaran' => $idxSasaran !== false ? (int)($sheetRow[$idxSasaran] ?? 0) : 0,
        'target_tahunan' => $idxTargetThn !== false ? (int)($sheetRow[$idxTargetThn] ?? 0) : 0,
        'target_bulanan' => $targetBln,
        'capaian' => $capaian,
        'persentase' => $persentase,
        'status' => hitungStatus($persentase),
    ];
}

/**
 * Baca sheet TARGET yang tidak memiliki kolom BULAN.
 * Setiap baris dianggap sebagai target tahunan untuk satu puskesmas + indikator.
 * Target bulanan = Target Tahunan / 12 (dibulatkan ke bawah jika perlu).
 *
 * Mengembalikan map: "tahun|puskesmas|indikator" => data target
 */
function buildTargetMapFromYearlySheet(array $data, int $headerIdx): array {
    $header = array_map(fn($h) => normalizeHeaderName((string)$h), $data[$headerIdx] ?? []);
    $idxTahun = getHeaderIndex($header, ['TAHUN']);
    $idxPuskesmas = getHeaderIndex($header, ['PUSKESMAS']);
    $idxIndikator = getHeaderIndex($header, ['INDIKATOR']);
    $idxSasaran = getHeaderIndex($header, ['SASARAN']);
    $idxTargetThn = getHeaderIndex($header, ['TARGET TAHUNAN']);
    $idxTargetBln = getHeaderIndex($header, ['TARGET BULANAN']);

    if ($idxPuskesmas === false || $idxIndikator === false) {
        return [];
    }

    $map = [];
    for ($i = $headerIdx + 1; $i < count($data); $i++) {
        $row = $data[$i];
        $puskesmas = trim((string)($row[$idxPuskesmas] ?? ''));
        $indikator  = trim((string)($row[$idxIndikator] ?? ''));
        if ($puskesmas === '' || $indikator === '') {
            continue;
        }

        $tahun = $idxTahun !== false ? (int)($row[$idxTahun] ?? 2026) : 2026;
        $targetTahunan = $idxTargetThn !== false ? (float)($row[$idxTargetThn] ?? 0) : 0.0;

        // Jika ada kolom TARGET BULANAN eksplisit, gunakan itu; jika tidak, bagi target tahunan dengan 12.
        if ($idxTargetBln !== false && (float)($row[$idxTargetBln] ?? 0) > 0) {
            $targetBulanan = (float)($row[$idxTargetBln]);
        } else {
            $targetBulanan = $targetTahunan > 0 ? round($targetTahunan / 12, 2) : 0.0;
        }

        $sasaran = $idxSasaran !== false ? (int)($row[$idxSasaran] ?? 0) : 0;

        $key = implode('|', [$tahun, strtoupper($puskesmas), strtoupper($indikator)]);
        $map[$key] = [
            'tahun'          => $tahun,
            'puskesmas'      => $puskesmas,
            'indikator'      => $indikator,
            'sasaran'        => $sasaran,
            'target_tahunan' => (int)$targetTahunan,
            'target_bulanan' => $targetBulanan,
        ];
    }

    return $map;
}

/**
 * Mencari index baris yang berfungsi sebagai header.
 */
function findHeaderRowIndex(array $data): int {
    $limit = min(5, count($data));
    for ($i = 0; $i < $limit; $i++) {
        $rowStr = strtoupper(implode(' ', array_map('strval', $data[$i] ?? [])));
        if (str_contains($rowStr, 'PUSKESMAS') && (str_contains($rowStr, 'INDIKATOR') || str_contains($rowStr, 'TARGET') || str_contains($rowStr, 'CAPAIAN'))) {
            return $i;
        }
    }
    return 0; // Default fallback
}

/**
 * Cari sheet-sheet di workbook yang berisi struktur data SIPANDA.
 * Prioritas diberikan ke sheet DATABASE SIPANDA, lalu sheet TARGET, lalu sheet REALISASI.
 *
 * @return array<int, array{name: string, data: array, kind: string, header_idx: int}>
 */
function cariSheetDataSipanda(\PhpOffice\PhpSpreadsheet\Spreadsheet $spreadsheet): array {
    $candidates = [];
    foreach ($spreadsheet->getSheetNames() as $sheetName) {
        $sheet = $spreadsheet->getSheetByName($sheetName);
        $data = $sheet->toArray(null, true, true, false);
        if (count($data) < 2) {
            continue;
        }
        
        $headerIdx = findHeaderRowIndex($data);
        $header = array_map(fn($h) => normalizeHeaderName((string)$h), $data[$headerIdx] ?? []);
        $headerSet = array_flip($header);
        $kind = detectSheetKind($headerSet, $sheetName);

        if ($kind !== null) {
            $candidates[] = ['name' => $sheetName, 'data' => $data, 'kind' => $kind, 'header_idx' => $headerIdx];
        }
    }

    usort($candidates, function (array $a, array $b): int {
        $priority = ['database' => 0, 'target' => 1, 'realisasi' => 2];
        $diff = ($priority[$a['kind']] ?? 99) <=> ($priority[$b['kind']] ?? 99);
        if ($diff !== 0) {
            return $diff;
        }

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
 * Jika workbook baru memisahkan data ke sheet TARGET dan REALISASI,
 * sistem akan menggabungkan keduanya secara otomatis.
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
            'errors' => ['Tidak ada sheet data yang cocok. Sheet yang diperlukan harus memiliki kolom TAHUN, PUSKESMAS, INDIKATOR, dan minimal TARGET atau CAPAIAN sesuai struktur Target/Realisasi, atau struktur lengkap DATABASE SIPANDA.'],
            'total_baris_dibaca' => 0,
            'sheets_dibaca' => $spreadsheet->getSheetNames(),
        ];
    }

    $rows = [];
    $errors = [];
    $totalBarisDibaca = 0;
    $databaseCandidates = [];
    $targetRows = [];
    $realisasiRows = [];

    // Deteksi apakah ada sheet TARGET tanpa kolom BULAN (format tahunan)
    $yearlyTargetMap = []; // key: "tahun|puskesmas|indikator"
    $hasYearlyTarget = false;

    foreach ($candidates as $candidate) {
        $data = $candidate['data'];
        $headerIdx = $candidate['header_idx'];
        $header = array_map(fn($h) => normalizeHeaderName((string)$h), $data[$headerIdx] ?? []);
        $kind = $candidate['kind'];

        if ($kind === 'database') {
            $databaseCandidates[] = $candidate;
            continue;
        }

        $totalBarisDibaca += max(0, count($data) - ($headerIdx + 1));

        if ($kind === 'target') {
            // Cek apakah sheet target ini punya kolom BULAN
            $hasBulanCol = getHeaderIndex($header, ['BULAN', 'NO BULAN', 'NO. BULAN']) !== false;
            if (!$hasBulanCol) {
                // Format target tahunan: satu baris per puskesmas+indikator, tanpa bulan
                $hasYearlyTarget = true;
                $yearlyTargetMap = array_merge($yearlyTargetMap, buildTargetMapFromYearlySheet($data, $headerIdx));
                continue;
            }
        }

        for ($i = $headerIdx + 1; $i < count($data); $i++) {
            $sheetRow = $data[$i];
            $row = buildRowFromSheetData($sheetRow, $header, $candidate['name'], $i + 1, $kind);
            if ($row === null) {
                continue;
            }

            if ($kind === 'target') {
                $targetRows[] = $row;
            }
            if ($kind === 'realisasi') {
                $realisasiRows[] = $row;
            }
        }
    }

    if (!empty($databaseCandidates)) {
        foreach ($databaseCandidates as $candidate) {
            $data = $candidate['data'];
            $headerIdx = $candidate['header_idx'];
            $header = array_map(fn($h) => normalizeHeaderName((string)$h), $data[$headerIdx] ?? []);
            $idxTahun = getHeaderIndex($header, ['TAHUN']);
            $idxNoBulan = getHeaderIndex($header, ['NO BULAN', 'NO. BULAN']);
            $idxBulan = getHeaderIndex($header, ['BULAN']);
            $idxPuskesmas = getHeaderIndex($header, ['PUSKESMAS']);
            $idxIndikator = getHeaderIndex($header, ['INDIKATOR']);
            $idxSasaran = getHeaderIndex($header, ['SASARAN']);
            $idxTargetThn = getHeaderIndex($header, ['TARGET TAHUNAN']);
            $idxTargetBln = getHeaderIndex($header, ['TARGET BULANAN']);
            $idxCapaian = getHeaderIndex($header, ['CAPAIAN', 'CAPAIAN BULANAN']);

            if ($idxPuskesmas === false || $idxIndikator === false || $idxTargetBln === false || $idxCapaian === false) {
                $errors[] = "Sheet {$candidate['name']}: kolom wajib tidak ditemukan untuk struktur database";
                continue;
            }

            if ($idxNoBulan === false && $idxBulan === false) {
                $errors[] = "Sheet {$candidate['name']}: kolom BULAN atau NO BULAN tidak ditemukan";
                continue;
            }

            for ($i = $headerIdx + 1; $i < count($data); $i++) {
                $sheetRow = $data[$i];
                $namaPuskesmas = trim((string)($sheetRow[$idxPuskesmas] ?? ''));
                $namaIndikator = trim((string)($sheetRow[$idxIndikator] ?? ''));
                if ($namaPuskesmas === '' || $namaIndikator === '') {
                    continue;
                }

                $bulan = parseBulan($sheetRow, $idxNoBulan, $idxBulan);
                if ($bulan === null) {
                    $errors[] = "Sheet {$candidate['name']}, Baris " . ($i + 1) . ": Bulan tidak dikenali";
                    continue;
                }

                $targetBln = (float)($sheetRow[$idxTargetBln] ?? 0);
                $capaian = (float)($sheetRow[$idxCapaian] ?? 0);
                $persentase = $targetBln > 0 ? round(($capaian / $targetBln) * 100, 2) : 0;

                $rows[] = [
                    'tahun' => $idxTahun !== false ? (int)($sheetRow[$idxTahun] ?? 2026) : 2026,
                    'bulan' => $bulan,
                    'puskesmas' => $namaPuskesmas,
                    'indikator' => $namaIndikator,
                    'sasaran' => $idxSasaran !== false ? (int)($sheetRow[$idxSasaran] ?? 0) : 0,
                    'target_tahunan' => $idxTargetThn !== false ? (int)($sheetRow[$idxTargetThn] ?? 0) : 0,
                    'target_bulanan' => $targetBln,
                    'capaian' => $capaian,
                    'persentase' => $persentase,
                    'status' => hitungStatus($persentase),
                ];
            }
        }
    } elseif ($hasYearlyTarget) {
        // Mode: sheet TARGET tahunan (tanpa kolom BULAN) + sheet REALISASI bulanan
        // Kunci target: tahun|puskesmas|indikator
        // Kunci realisasi: tahun|bulan|puskesmas|indikator -> cocokkan ke target tahunan
        foreach ($realisasiRows as $realisasiRow) {
            $targetKey = implode('|', [$realisasiRow['tahun'], strtoupper($realisasiRow['puskesmas']), strtoupper($realisasiRow['indikator'])]);
            $targetInfo = $yearlyTargetMap[$targetKey] ?? null;

            $targetBulanan = $targetInfo['target_bulanan'] ?? 0.0;
            $capaian = $realisasiRow['capaian'];
            $persentase = $targetBulanan > 0 ? round(($capaian / $targetBulanan) * 100, 2) : 0;

            $rows[] = [
                'tahun'          => $realisasiRow['tahun'],
                'bulan'          => $realisasiRow['bulan'],
                'puskesmas'      => $realisasiRow['puskesmas'],
                'indikator'      => $realisasiRow['indikator'],
                'sasaran'        => $targetInfo['sasaran'] ?? 0,
                'target_tahunan' => $targetInfo['target_tahunan'] ?? 0,
                'target_bulanan' => $targetBulanan,
                'capaian'        => $capaian,
                'persentase'     => $persentase,
                'status'         => hitungStatus($persentase),
            ];
        }
    } else {
        // Mode: sheet TARGET bulanan + sheet REALISASI bulanan, gabung per bulan
        $targetMap = [];
        $realisasiMap = [];

        foreach ($targetRows as $row) {
            $key = implode('|', [$row['tahun'], $row['bulan'], $row['puskesmas'], $row['indikator']]);
            $targetMap[$key] = $row;
        }

        foreach ($realisasiRows as $row) {
            $key = implode('|', [$row['tahun'], $row['bulan'], $row['puskesmas'], $row['indikator']]);
            $realisasiMap[$key] = $row;
        }

        $keys = array_unique(array_merge(array_keys($targetMap), array_keys($realisasiMap)));
        sort($keys, SORT_STRING);

        foreach ($keys as $key) {
            $targetRow = $targetMap[$key] ?? null;
            $realisasiRow = $realisasiMap[$key] ?? null;

            if ($targetRow === null && $realisasiRow === null) {
                continue;
            }

            $targetBulanan = (float)($targetRow['target_bulanan'] ?? 0);
            $capaian = (float)($realisasiRow['capaian'] ?? 0);
            $persentase = $targetBulanan > 0 ? round(($capaian / $targetBulanan) * 100, 2) : 0;

            $rows[] = [
                'tahun'          => (int)($targetRow['tahun'] ?? $realisasiRow['tahun'] ?? 2026),
                'bulan'          => (int)($targetRow['bulan'] ?? $realisasiRow['bulan'] ?? 1),
                'puskesmas'      => (string)($targetRow['puskesmas'] ?? $realisasiRow['puskesmas'] ?? ''),
                'indikator'      => (string)($targetRow['indikator'] ?? $realisasiRow['indikator'] ?? ''),
                'sasaran'        => (int)($targetRow['sasaran'] ?? 0),
                'target_tahunan' => (int)($targetRow['target_tahunan'] ?? 0),
                'target_bulanan' => $targetBulanan,
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
