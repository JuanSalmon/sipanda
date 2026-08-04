<<<<<<< HEAD
# SIPANDA PTM

Sistem Informasi Pemantauan Data - Penyakit Tidak Menular.
Dashboard publik (tanpa login) + panel admin untuk upload data dari Excel.

## Arsitektur Data (PENTING)

Data PTM (puskesmas, indikator, capaian) **tidak disimpan di database**.
Database hanya menyimpan akun admin + riwayat upload (metadata).

Alurnya:
1. Admin upload file `.xlsx` lewat panel admin.
2. Sistem validasi (cek sheet & kolom wajib ada), lalu file disimpan sebagai
   `uploads/data_sipanda.xlsx` — **menggantikan file sebelumnya**.
3. Setiap kali dashboard publik atau API dibuka, sistem **membaca ulang file itu langsung**
   dan menghitung semua angka (scoreboard, chart, tabel) secara real-time dari isi file.

Konsekuensinya:
- Tidak perlu proses "import ke database" — upload = data langsung update.
- Karena dibaca ulang tiap request, untuk data besar/traffic tinggi sebaiknya ditambah caching
  (misalnya cache hasil `bacaDataSipanda()` beberapa menit) — belum diimplementasi di versi ini.
- Riwayat data lama tidak tersimpan; upload baru menimpa total data yang tampil.

## Cara Install (XAMPP / lokal)

1. Copy folder `sipanda` ke `htdocs` (XAMPP) atau document root server kamu.
2. Buat database: import `database/schema.sql` (hanya berisi tabel admin_users & upload_log).
3. Sesuaikan kredensial database di `config/database.php` jika perlu.
4. Install dependency PHP (butuh Composer, untuk PhpSpreadsheet):
   ```
   composer install
   ```
5. Buat akun admin pertama — buka di browser: `http://localhost/sipanda/setup.php`
   lalu **hapus file setup.php** setelah dijalankan.
6. Login admin di `http://localhost/sipanda/admin/login.php`
   (default: username `admin`, password `admin123` — segera ganti), lalu upload file Excel pertama.
7. Akses dashboard publik di `http://localhost/sipanda/index.php`.

## Format File Excel yang Diupload

Sheet harus bernama **DATABASE SIPANDA**, dengan header di baris pertama (sesuai format asli):

| TAHUN | NO BULAN | BULAN | PUSKESMAS | INDIKATOR | SASARAN | TARGET TAHUNAN | TARGET BULANAN | CAPAIAN | PERSENTASE | STATUS | CATATAN |
|---|---|---|---|---|---|---|---|---|---|---|---|

- **PUSKESMAS**: harus salah satu dari 12 nama yang sudah terdaftar di `includes/excel_reader.php`
  (Baa, Batutua, Busalangga, Delha, Eahun, Feapopi, Korbafo, Ndao, Oelaba, Oele, Sonimanu, Sotimori).
- **INDIKATOR**: Usia Produktif / Hipertensi / Diabetes Mellitus / HPV DNA.
- **NO BULAN**: angka 1-12, sumber utama penentu bulan. Fallback ke kolom **BULAN** (nama bulan) kalau tidak ada.
- **PERSENTASE** dan **STATUS** di file **diabaikan** — dihitung ulang saat file dibaca
  (PERSENTASE = CAPAIAN / TARGET BULANAN × 100; STATUS: ≥100% Tercapai, 70–99% Perlu Ditingkatkan, <70% Belum Tercapai).
- **CATATAN** diabaikan.
- Baris dengan Puskesmas/Indikator/Bulan yang tidak dikenali akan dilewati (muncul di daftar error di panel admin), baris lain tetap diproses.

## Struktur Folder

```
sipanda/
├── admin/                    # Login, dashboard admin, proses upload
│   └── upload_process.php    # Validasi file lalu simpan ke uploads/data_sipanda.xlsx
├── api/data.php               # Baca file Excel langsung + agregasi -> JSON untuk dashboard
├── assets/                    # CSS & JS
├── config/database.php        # Koneksi DB (khusus admin) + path file Excel aktif
├── database/schema.sql        # Hanya tabel admin_users & upload_log
├── includes/
│   ├── excel_reader.php       # Baca & validasi isi file Excel (dipakai admin & api)
│   ├── functions.php          # Helper status/warna/format
│   └── auth.php               # Guard login admin
├── uploads/data_sipanda.xlsx  # File Excel aktif (dibuat otomatis saat upload pertama)
├── index.php                  # Dashboard publik (landing page)
└── setup.php                  # Jalankan sekali untuk buat akun admin, lalu hapus
```
=======
# sipanda
>>>>>>> c91877eea4c376895ed3c3fd05400fe3ab1d67fa
