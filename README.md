# Sistem Informasi Komoditas Karantina

Aplikasi PHP untuk mengelola data lalu lintas komoditas karantina Kalimantan Selatan, memublikasikannya ke board publik, dan mengimpor data dari Excel.

## Fitur

- Board publik untuk lalu lintas domestik masuk/keluar, ekspor, dan impor.
- Ringkasan, grafik komoditas, filter periode bulanan, dan Mode Grafik tren bulanan antar tahun.
- Panel admin untuk mengimpor data Excel kategori hewan, ikan, dan tumbuhan.
- Kelola data untuk meninjau, mengubah, menerbitkan, menghapus, menambah, dan mengekspor data.
- Pengelolaan akun admin dan peran pengguna.

## Persyaratan

- PHP 8.2 atau lebih baru, 64-bit, dengan ekstensi PDO MySQL.
- MySQL atau MariaDB.
- Composer.
- Ekstensi PhpSpreadsheet: `ctype`, `dom`, `fileinfo`, `filter`, `gd`, `iconv`, `libxml`, `mbstring`, `simplexml`, `xml`, `xmlreader`, `xmlwriter`, `zip`, dan `zlib`.

## Menjalankan secara lokal

1. Pasang dependensi:

   ```powershell
   composer install
   ```

2. Siapkan database MySQL sesuai skema di `database/setup.sql`. Sebelum menjalankan skrip setup atau migrasi pada database yang sudah berisi data, buat cadangan dan periksa skripnya terlebih dahulu.
3. Buat `config/database.local.php` secara lokal. File ini tidak boleh di-commit dan harus mengembalikan array string dengan kunci `host`, `name`, `user`, dan `password`.
4. Jalankan aplikasi melalui Apache/PHP lokal, misalnya Laragon, lalu buka `index.php`.
5. Masuk ke halaman admin melalui tombol **Login** pada board publik.

Jangan gunakan skrip reset pada database produksi. Kredensial admin awal harus segera diganti setelah instalasi.

## Alur penggunaan

1. Masuk sebagai admin.
2. Unggah file Excel pada kategori yang sesuai, atau tambahkan data satu per satu.
3. Tinjau dan kelola data pada **Kelola & Publish Data**. Pastikan jenis lalu lintas, kategori, periode, nilai, volume, dan lokasi benar.
4. Publikasikan data yang sudah diverifikasi.
5. Periksa hasilnya pada board publik. Buka **Mode Grafik** untuk membandingkan tren bulanan antar tahun.

## Pengujian

Tes helper aplikasi tidak memerlukan koneksi database:

```powershell
php tests\run.php
```

Validasi sintaks seluruh file PHP aplikasi (melewati direktori dependensi):

```powershell
Get-ChildItem -Recurse -Filter *.php |
    Where-Object { $_.FullName -notmatch '\\vendor\\' } |
    ForEach-Object { php -l $_.FullName }
```

Pemeriksaan ekstensi yang dibutuhkan dependensi:

```powershell
composer check-platform-reqs
```

Daftar pemeriksaan alur browser dan cakupan tes dijelaskan di [docs/testing-checklist.md](docs/testing-checklist.md). Panduan Mode Grafik tersedia di [docs/mode-grafik.md](docs/mode-grafik.md).

## Deploy

Push ke branch `master` menjalankan tes helper, pemeriksaan sintaks PHP, lalu deploy FTP melalui `.github/workflows/deploy.yml`. Deploy hanya berjalan jika pemeriksaan berhasil. Host, username, dan password FTP harus disediakan sebagai GitHub Actions secrets; jangan simpan rahasia di repository. Alur browser dan database tetap perlu diuji manual di lingkungan pengujian sebelum rilis.
