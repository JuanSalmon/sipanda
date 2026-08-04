<?php
// SIPANDA PTM - Fungsi bantu bersama

function hitungStatus(float $persentase): string {
    if ($persentase >= 100) return 'Tercapai';
    if ($persentase >= 70) return 'Perlu Ditingkatkan';
    return 'Belum Tercapai';
}

function warnaStatus(string $status): string {
    return match ($status) {
        'Tercapai' => '#22c55e',            // hijau
        'Perlu Ditingkatkan' => '#f59e0b',  // oranye
        default => '#ef4444',               // merah
    };
}

function formatPersen(float $val): string {
    return number_format($val, 1) . '%';
}

function namaBulan(int $bulan): string {
    $bulanList = [1=>'Jan',2=>'Feb',3=>'Mar',4=>'Apr',5=>'Mei',6=>'Jun'];
    return $bulanList[$bulan] ?? (string)$bulan;
}

/**
 * Hitung persentase gabungan dari sekumpulan baris data SIPANDA dengan cara
 * menjumlahkan CAPAIAN dan TARGET BULANAN dulu, baru dibagi (weighted average).
 *
 * PENTING: JANGAN menghitung ini dengan merata-ratakan kolom 'persentase' tiap
 * baris (array_sum(persentase)/count). Kalau ada baris dengan TARGET BULANAN
 * kecil (mis. 3-4), CAPAIAN sedikit saja sudah bikin persentase baris itu
 * meledak (200%, 800%, dst), dan karena rata-rata tak tertimbang, satu-dua
 * baris seperti itu bisa menyeret rata-rata keseluruhan jadi ribuan persen —
 * ini penyebab bug "Usia Produktif 6412.6%" yang tidak konsisten dengan
 * total capaian/target aslinya.
 *
 * @param array $rows array baris hasil bacaDataSipanda() (harus punya key 'capaian' & 'target_bulanan')
 */
function persenGabungan(array $rows): float {
    $totalCapaian = array_sum(array_column($rows, 'capaian'));
    $totalTarget  = array_sum(array_column($rows, 'target_bulanan'));
    if ($totalTarget <= 0) return 0.0;
    return round(($totalCapaian / $totalTarget) * 100, 1);
}