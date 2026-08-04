# Summary — SIPANDA PTM

Dashboard monitoring capaian PTM (Penyakit Tidak Menular) 12 Puskesmas, dengan data diambil
langsung dari file Excel — **tanpa disimpan ke database**.

## Konsep Utama

| Aspek | Keterangan |
|---|---|
| Sumber data PTM | File Excel (`.xlsx`), sheet **DATABASE SIPANDA** |
| Penyimpanan data PTM | **Tidak ada** — dibaca ulang langsung dari file setiap kali dashboard/API diakses |
| Isi database | Hanya akun admin (login) dan riwayat upload (nama file, jumlah baris, waktu) |
| Update data | Admin upload file baru → file lama otomatis tergantikan → dashboard langsung berubah |

## Alur Kerja

1. **Admin login** di `/admin/login.php`.
2. **Admin upload** file Excel (`.xlsx`) di `/admin/dashboard.php`.
3. Sistem **validasi** file: cek sheet "DATABASE SIPANDA" ada, cek kolom wajib ada
   (PUSKESMAS, INDIKATOR, SASARAN, TARGET TAHUNAN, TARGET BULANAN, CAPAIAN, dan NO BULAN/BULAN).
4. Kalau valid, file disimpan sebagai `uploads/data_sipanda.xlsx` (menggantikan file sebelumnya).
   Baris dengan nama Puskesmas/Indikator/Bulan yang tidak dikenali dilewati dan dilaporkan ke admin,
   baris lain tetap diproses.
5. **Dashboard publik** (`/index.php`, tanpa login) memanggil `/api/data.php`, yang membaca file
   Excel aktif langsung, menghitung PERSENTASE & STATUS (bukan dari file — dihitung ulang oleh
   sistem supaya konsisten), lalu mengembalikan JSON untuk 5 komponen dashboard.

## Rumus & Aturan

- **PERSENTASE per baris** (per Puskesmas-Indikator-Bulan) = CAPAIAN ÷ TARGET BULANAN × 100
  (dihitung di `excel_reader.php`, dipakai untuk status per baris).
- **STATUS**: ≥100% Tercapai (hijau) · 70–99% Perlu Ditingkatkan (oranye) · <70% Belum Tercapai (merah)
- **Persentase agregat** (scoreboard, line chart, bar chart, doughnut, tabel monitoring) = **total
  CAPAIAN ÷ total TARGET BULANAN** dari baris-baris yang digabung (bukan rata-rata dari kolom
  PERSENTASE per baris). Dihitung lewat fungsi `persenGabungan()` di `functions.php`.
  *(Lihat bagian "Riwayat Debugging" — ini hasil perbaikan dari bug awal.)*

## 5 Komponen Dashboard

1. 4 scoreboard — persentase agregat (total capaian/total target) per indikator
2. Line chart — tren persentase agregat per bulan (Usia Produktif, Hipertensi, Diabetes Mellitus)
3. Bar chart — ranking 12 Puskesmas berdasarkan skor gabungan (rata-rata dari 4 persentase agregat
   per indikator, tiap indikator berbobot sama)
4. Doughnut — jumlah Puskesmas per status, dengan dropdown filter per indikator
5. Tabel monitoring — semua Puskesmas × indikator (persentase agregat Jan–Jun), dengan kolom search

## Struktur Project

```
sipanda/
├── admin/
│   ├── login.php            # Login admin
│   ├── logout.php
│   ├── dashboard.php        # Upload file + preview 20 baris terakhir + daftar error
│   └── upload_process.php   # Validasi file lalu simpan ke uploads/data_sipanda.xlsx
├── api/
│   └── data.php             # Baca file Excel + agregasi -> JSON (dipanggil dashboard.js)
├── assets/
│   ├── css/style.css
│   └── js/
│       ├── dashboard.js     # Render scoreboard, chart, doughnut, tabel + search
│       └── admin.js         # Handle submit form upload via AJAX
├── config/
│   └── database.php         # Koneksi DB admin + path file Excel aktif (EXCEL_DATA_PATH)
├── database/
│   └── schema.sql           # Tabel admin_users & upload_log SAJA
├── includes/
│   ├── excel_reader.php     # Baca & validasi isi Excel (dipakai bareng oleh admin & api)
│   ├── functions.php        # hitungStatus(), warnaStatus(), formatPersen(), namaBulan(),
│   │                         # persenGabungan()
│   └── auth.php             # Guard session login admin
├── uploads/
│   └── data_sipanda.xlsx    # File Excel aktif (terbuat otomatis saat upload pertama)
├── index.php                # Dashboard publik / landing page
├── setup.php                # Jalankan sekali untuk buat akun admin pertama, lalu hapus
├── composer.json             # Dependency: phpoffice/phpspreadsheet
└── README.md                # Panduan install & format file Excel
```

## Yang Perlu Diperhatikan

- **Performa**: karena file Excel dibaca ulang dari disk setiap request (bukan query database
  yang sudah terindeks), untuk 288 baris masih cepat. Kalau nanti jumlah baris/traffic naik jauh,
  bisa ditambah caching sederhana (simpan hasil parsing di session/file cache beberapa menit).
- **Tidak ada histori**: upload baru menimpa total data yang tampil di dashboard. Kalau perlu
  histori tiap upload, tabel `upload_log` sudah mencatat nama file & waktu, tapi isi datanya sendiri
  tidak disimpan.
- **Nama Puskesmas & Indikator** divalidasi terhadap daftar tetap di `includes/excel_reader.php`
  (`PUSKESMAS_VALID`, `INDIKATOR_VALID`) — kalau nanti ada Puskesmas/indikator baru, daftar ini
  perlu diupdate manual di kode.
- Data contoh yang diupload (288 baris, 12 Puskesmas × 4 indikator × 6 bulan Jan–Jun 2026) sudah
  dicek strukturnya dan cocok dengan parser di atas.

## Riwayat Debugging & Perbaikan

### 1. Persentase agregat meledak (mis. Usia Produktif tampil 6412.6%)

- **Gejala**: scoreboard menampilkan persentase ribuan persen, tidak sinkron dengan angka
  "total capaian / total target" yang ditampilkan di kartu yang sama.
- **Penyebab**: `data.php` versi awal menghitung persentase agregat sebagai **rata-rata tak
  tertimbang** dari kolom PERSENTASE per baris (`array_sum(persentase) / count`). Karena ada
  baris-baris dengan TARGET BULANAN kecil, CAPAIAN sedikit saja membuat PERSENTASE baris itu
  meledak (ratusan persen), dan rata-rata tak tertimbang ikut tertarik jauh dari kenyataan.
- **Perbaikan**: menambah fungsi `persenGabungan()` di `functions.php` — menjumlahkan CAPAIAN dan
  TARGET BULANAN dari sekumpulan baris dulu, baru dibagi (weighted average: total ÷ total).
  Diterapkan ke seluruh agregasi di `data.php` (scoreboard, line chart, bar chart, doughnut, tabel).
- **Status**: **selesai diperbaiki**, sudah di-push ke GitHub.

### 2. Tabel monitoring: Usia Produktif Baa (64500%) & Batutua (12050%)

- **Gejala**: dua Puskesmas menampilkan persentase Usia Produktif yang sangat ekstrem
  (capaian ribuan, target hanya 6 selama 6 bulan).
- **Penyebab**: **bukan bug kode** — perhitungan `data.php` sudah benar secara matematis
  berdasarkan angka yang dibaca dari Excel. Sumber masalah ada di **data Excel itu sendiri**:
  kolom TARGET BULANAN untuk indikator Usia Produktif di Puskesmas Baa & Batutua totalnya hanya 6
  selama Jan–Jun (≈1/bulan), jauh di luar skala normal dibanding Puskesmas lain (ratusan–ribuan).
- **Status**: **belum diperbaiki** — perlu koreksi manual pada file Excel sumber (`TARGET BULANAN`
  untuk Baa & Batutua, indikator Usia Produktif, Jan–Jun).