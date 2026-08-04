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
