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

- **PERSENTASE** = CAPAIAN ÷ TARGET BULANAN × 100
- **STATUS**: ≥100% Tercapai (hijau) · 70–99% Perlu Ditingkatkan (oranye) · <70% Belum Tercapai (merah)
- **Scoreboard/tabel** pakai rata-rata PERSENTASE bulanan (Jan–Jun) per indikator/puskesmas

## 5 Komponen Dashboard

1. 4 scoreboard — rata-rata % + total capaian/target per indikator
2. Line chart — tren rata-rata % per bulan (Usia Produktif, Hipertensi, Diabetes Mellitus)
3. Bar chart — ranking 12 Puskesmas berdasarkan skor gabungan 4 indikator
4. Doughnut — jumlah Puskesmas per status, dengan dropdown filter per indikator
5. Tabel monitoring — semua Puskesmas × indikator, dengan kolom search

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
│   ├── functions.php        # hitungStatus(), warnaStatus(), formatPersen(), namaBulan()
│   └── auth.php             # Guard session login admin
├── uploads/
│   └── data_sipanda.xlsx    # File Excel aktif (terbuat otomatis saat upload pertama)
├── index.php                # Dashboard publik / landing page
├── setup.php                # Jalankan sekali untuk buat akun admin pertama, lalu hapus
├── composer.json            # Dependency: phpoffice/phpspreadsheet
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
