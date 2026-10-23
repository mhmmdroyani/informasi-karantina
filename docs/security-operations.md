# Operasional Keamanan

## Tindakan segera untuk kredensial yang pernah ter-commit

File migrasi lama pernah memuat kredensial database secara langsung. Menghapus nilainya dari versi terbaru tidak menghapusnya dari riwayat Git.

1. Ganti password akun database melalui panel hosting dan perbarui konfigurasi database di server serta `config/database.local.php` lokal.
2. Ganti kredensial FTP yang pernah digunakan. Jangan gunakan kembali password lama.
3. Setelah host memastikan FTPS tersedia, simpan host, username, dan password deploy sebagai GitHub Actions secrets. Set repository variable `FTPS_DEPLOY_ENABLED` ke `true` untuk membuka job deploy.
4. Jangan menyalin nilai rahasia ke source control, issue, log, atau chat.
5. Pertimbangkan pembersihan riwayat Git terkoordinasi jika kebijakan organisasi mengharuskan penghapusan kredensial lama. Rotasi tetap wajib meski riwayat dibersihkan.

Sampai dukungan FTPS dikonfirmasi dan variable diaktifkan, workflow hanya menjalankan tes; deploy otomatis sengaja dilewati. Jika host tidak mendukung FTPS, pilih layanan deploy yang menyediakan protokol terenkripsi sebelum mengaktifkan deploy kembali.

## Database dan skrip maintenance

- File `config/database.local.php` dikecualikan dari Git. Pastikan file itu tersedia dan memiliki kredensial baru pada mesin aplikasi.
- `database/migrate.php`, `database/upgrade_schema.php`, `database/migrate_monthly_raw_data.php`, `database/rename_domisili_to_domestik.php`, dan `database/reset.php` hanya boleh dijalankan melalui PHP CLI.
- Untuk setup/reset, berikan kredensial koneksi melalui environment `SILAKOM_DB_HOST`, `SILAKOM_DB_NAME`, `SILAKOM_DB_USER`, dan `SILAKOM_DB_PASSWORD`.
- Setup/reset memerlukan `SILAKOM_INITIAL_ADMIN_PASSWORD` minimal 16 karakter saat membuat akun admin awal. Skrip migrasi tidak mengganti password akun admin yang sudah ada.
- `database/reset.php` menghapus seluruh database yang dipilih. Jangan jalankan terhadap produksi.

Contoh environment PowerShell (isi nilainya hanya di terminal lokal tepercaya):

```powershell
$env:SILAKOM_DB_HOST = "127.0.0.1"
$env:SILAKOM_DB_NAME = "db_ekspor_karantina"
$env:SILAKOM_DB_USER = "root"
$env:SILAKOM_DB_PASSWORD = ""
$env:SILAKOM_INITIAL_ADMIN_PASSWORD = "<password-kuat-minimal-16-karakter>"
php database\migrate.php
Remove-Item Env:SILAKOM_DB_HOST, Env:SILAKOM_DB_NAME, Env:SILAKOM_DB_USER, Env:SILAKOM_DB_PASSWORD, Env:SILAKOM_INITIAL_ADMIN_PASSWORD
```
