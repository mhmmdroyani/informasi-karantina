# Mode Grafik

Mode Grafik membandingkan tren bulanan antar tahun untuk jenis lalu lintas dan kategori komoditas yang dipilih.

- Pilihan **Tahun Awal** dan **Tahun Akhir** menentukan rentang tahun yang ditampilkan, termasuk tahun di antaranya.
- Grafik menampilkan Januari sampai Desember dengan garis terpisah untuk setiap tahun.
- Tahun yang tersedia adalah tahun dengan setidaknya satu periode data terpublikasi.
- Nilai, volume, frekuensi, dan jumlah daerah/negara tujuan ditampilkan sebagai grafik terpisah.
- Bulan tanpa data diisi nol. Volume disetarakan ke kilogram untuk perbandingan.

## Pengujian

Jalankan pengujian unit tanpa koneksi database:

```powershell
php tests\annual_comparison_test.php
```

Pemeriksaan manual pada aplikasi:

1. Buka **Mode Grafik**, lalu pilih beberapa tahun.
2. Pastikan sumbu mendatar berisi Januari–Desember dan legenda membedakan tiap tahun.
3. Pastikan tahun tanpa data di antara tahun awal dan akhir tetap muncul sebagai garis nol.
4. Ubah jenis lalu lintas atau kategori, lalu pastikan pilihan tahun dan grafik mengikuti data terpublikasi untuk pilihan tersebut.
5. Kembali ke Board Publik dan pastikan filter periode bulanan tetap berfungsi.
