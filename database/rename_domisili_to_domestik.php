<?php
// database/rename_domisili_to_domestik.php - migrasi satu kali: domisili_keluar/masuk -> domestik_keluar/masuk
require_once __DIR__ . '/../config/koneksi.php';

try {
    foreach (['ekspor_komoditas', 'ekspor_settings'] as $table) {
        // 1. Tambahkan nilai enum baru sambil tetap mempertahankan nilai lama
        $pdo->exec("ALTER TABLE {$table} MODIFY jenis_kegiatan ENUM('ekspor','impor','domisili_keluar','domisili_masuk','domestik_keluar','domestik_masuk') NOT NULL DEFAULT 'ekspor'");

        // 2. Migrasikan data yang masih memakai nilai lama
        $pdo->exec("UPDATE {$table} SET jenis_kegiatan = 'domestik_keluar' WHERE jenis_kegiatan = 'domisili_keluar'");
        $pdo->exec("UPDATE {$table} SET jenis_kegiatan = 'domestik_masuk' WHERE jenis_kegiatan = 'domisili_masuk'");

        // 3. Hapus nilai enum lama setelah data bersih
        $pdo->exec("ALTER TABLE {$table} MODIFY jenis_kegiatan ENUM('ekspor','impor','domestik_keluar','domestik_masuk') NOT NULL DEFAULT 'ekspor'");

        echo "Selesai migrasi tabel {$table}\n";
    }

    echo "Migrasi domisili -> domestik berhasil.\n";
} catch (Exception $e) {
    echo "Migrasi gagal: " . $e->getMessage() . "\n";
}
