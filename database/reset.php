<?php
// database/reset.php - Reset database dari nol
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Skrip ini hanya boleh dijalankan melalui CLI.\n");
}

$database = 'db_ekspor_karantina';
$host = '127.0.0.1';
$user = 'root';
$pass = '';

$confirmed = in_array('--confirm', $argv ?? [], true);
if (!$confirmed) {
    echo "PERINGATAN: seluruh data database {$database} akan dihapus.\n";
    echo "Ketik RESET untuk melanjutkan: ";
    $answer = trim((string)fgets(STDIN));
    if ($answer !== 'RESET') {
        exit("Reset dibatalkan.\n");
    }
}

try {
    $pdo = new PDO("mysql:host={$host};charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $pdo->exec("DROP DATABASE IF EXISTS `{$database}`");
    $schema = file_get_contents(__DIR__ . '/setup.sql');
    if ($schema === false || trim($schema) === '') {
        throw new RuntimeException('File database/setup.sql tidak dapat dibaca.');
    }
    $pdo->exec($schema);
    $pdo->exec("USE `{$database}`");

    $adminHash = password_hash('admin123', PASSWORD_DEFAULT);
    $adminStmt = $pdo->prepare(
        "INSERT INTO admin_users (username, password, nama, role, is_active)
         VALUES (?, ?, ?, 'admin', 1)"
    );
    $adminStmt->execute(['admin', $adminHash, 'Administrator BKHIT']);

    $settingsStmt = $pdo->prepare(
        "INSERT INTO ekspor_settings
            (jenis_kegiatan, kategori, top_n, urutkan_berdasarkan, filter_mode,
             tanggal_awal, tanggal_akhir, tahun_awal, tahun_akhir, periode_label)
         VALUES (?, ?, 5, 'nilai_ekspor', 'tahun', '2026-01-01', '2026-12-31', 2026, 2026, 'TAHUN 2026')"
    );

    foreach (['ekspor', 'impor', 'domestik_keluar', 'domestik_masuk'] as $jenis) {
        foreach (['hewan', 'ikan', 'tumbuhan'] as $kategori) {
            $settingsStmt->execute([$jenis, $kategori]);
        }
    }

    echo "Reset database berhasil.\n";
    echo "Database: {$database}\n";
    echo "Admin: admin / admin123\n";
    echo "Pengaturan board dibuat: 12 kombinasi kegiatan dan kategori.\n";
} catch (Throwable $e) {
    fwrite(STDERR, "Reset database gagal: {$e->getMessage()}\n");
    exit(1);
}
