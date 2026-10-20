<?php
$host = getenv('SILAKOM_DB_HOST');
$database = getenv('SILAKOM_DB_NAME');
$user = getenv('SILAKOM_DB_USER');
$password = getenv('SILAKOM_DB_PASSWORD');

if (!$host || !$database || !$user || $password === false) {
    fwrite(STDERR, "Konfigurasi database SILAKOM belum lengkap.\n");
    exit(1);
}

try {
    $pdo = new PDO("mysql:host={$host};dbname={$database};charset=utf8mb4", $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    foreach ([
        'klasifikasi' => "ADD COLUMN klasifikasi VARCHAR(255) NULL AFTER kategori",
        'daerah_asal' => "ADD COLUMN daerah_asal TEXT NULL AFTER nilai_ekspor",
        'daerah_tujuan' => "ADD COLUMN daerah_tujuan TEXT NULL AFTER daerah_asal"
    ] as $column => $definition) {
        $statement = $pdo->query("SHOW COLUMNS FROM ekspor_komoditas LIKE " . $pdo->quote($column));
        if (!$statement->fetch()) {
            $pdo->exec("ALTER TABLE ekspor_komoditas {$definition}");
            echo "Kolom {$column} ditambahkan.\n";
        }
    }

    echo "Migrasi data rekap bulanan selesai.\n";
} catch (Exception $exception) {
    fwrite(STDERR, "Migrasi gagal: " . $exception->getMessage() . "\n");
    exit(1);
}

