# Summary — SIPANDA PTM

Dashboard monitoring capaian PTM (Penyakit Tidak Menular) 12 Puskesmas, dengan data diambil
langsung dari file Excel — **tanpa disimpan ke database**.

## Konsep Utama

| Aspek | Keterangan |
|---|---|
| Sumber data PTM | File Excel (`.xlsx`) dari workbook yang berisi sheet data SIPANDA. Saat ini sistem bisa membaca struktur lama **DATABASE SIPANDA** dan juga struktur baru yang dipisah ke sheet **TARGET** dan **REALISASI** |
| Penyimpanan data PTM | **Tidak ada** — dibaca ulang langsung dari file setiap kali dashboard/API diakses |
| Isi database | Hanya akun admin (login) dan riwayat upload (nama file, jumlah baris, waktu) |
| Update data | Admin upload file baru → file lama disimpan ke arsip `uploads/history/` → file baru dijadikan file aktif yang dibaca dashboard |

## Alur Kerja

1. **Admin login** di `/admin/login.php`.
2. **Admin upload** file Excel (`.xlsx`) di `/admin/dashboard.php`.
3. Sistem **validasi** file: scan seluruh sheet workbook dan deteksi struktur data yang cocok.
   - Struktur lama: sheet **DATABASE SIPANDA** dengan kolom lengkap (TAHUN, NO BULAN/BULAN,
     PUSKESMAS, INDIKATOR, SASARAN, TARGET TAHUNAN, TARGET BULANAN, CAPAIAN)
   - Struktur baru: sheet **TARGET** dan **REALISASI** yang dipisah, lalu sistem menggabungkan
     keduanya berdasarkan kombinasi Tahun–Bulan–Puskesmas–Indikator.
4. Kalau valid, file baru disimpan ke arsip `uploads/history/` dan salinan aktif dibuat sebagai
   `uploads/data_sipanda.xlsx` untuk dibaca dashboard. File lama tidak dihapus.
   Baris dengan data yang tidak lengkap/garis bulan tidak dikenali dilewati dan dilaporkan ke admin,
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
- **Histori upload tetap ada**: upload baru tidak lagi menghapus data lama secara permanen. File lama
  disimpan sebagai arsip di `uploads/history/`, dan file aktif yang dibaca dashboard tetap hanya satu
  `uploads/data_sipanda.xlsx`.
- **Nama Puskesmas & Indikator** tidak lagi dikunci ke daftar tetap. Sistem sekarang membaca
  nama apa pun yang muncul di sheet Excel, sehingga struktur baru dengan indikator/puskesmas
  tambahan bisa diproses tanpa mengubah kode secara manual, asalkan kolom yang dibutuhkan ada.
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

### 2. Struktur file Excel berubah menjadi multi-sheet

- **Gejala**: file Excel baru tidak lagi berisi satu sheet tunggal `DATABASE SIPANDA`.
  Beberapa workbook memisahkan data ke sheet `TARGET` dan `REALISASI` secara terpisah.
- **Penyebab**: parser lama hanya mengenali satu pola sheet tunggal; ia gagal membaca workbook yang
  memiliki beberapa sheet data sekaligus.
- **Perbaikan**: parser sekarang melakukan scan otomatis seluruh sheet, mendeteksi tipe sheet
  (`database`, `target`, `realisasi`), lalu menggabungkan data target dan realisasi untuk
  menghasilkan satu dataset konsisten yang dipakai dashboard.
- **Status**: **selesai diperbaiki**, sudah di-push ke GitHub.

### 3. Upload file baru sekarang menjaga arsip lama

- **Gejala**: saat upload file baru, file lama langsung tergantikan, sehingga riwayat data lama tidak
  bisa dipulihkan dari file aktif.
- **Penyebab**: alur upload sebelumnya memakai `move_uploaded_file()` langsung ke `uploads/data_sipanda.xlsx`.
- **Perbaikan**: file baru disimpan ke `uploads/history/` sebagai arsip, lalu salinan aktif dibuat ke
  `uploads/data_sipanda.xlsx`, sehingga dashboard tetap memakai file baru tanpa menghapus file lama.
- **Status**: **selesai diperbaiki**, sudah di-push ke GitHub.