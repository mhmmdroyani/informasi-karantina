<?php
// Skrip inisialisasi database & seed
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$host = getenv('SILAKOM_DB_HOST') ?: '127.0.0.1';
$user = getenv('SILAKOM_DB_USER') ?: 'root';
$pass = getenv('SILAKOM_DB_PASSWORD');
$pass = $pass === false ? '' : $pass;
$database = getenv('SILAKOM_DB_NAME') ?: 'db_ekspor_karantina';
if (!preg_match('/^[A-Za-z0-9_]+$/', $database)) {
    fwrite(STDERR, "Nama database SILAKOM tidak valid.\n");
    exit(1);
}

try {
    $pdo = new PDO("mysql:host={$host};charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sql = file_get_contents(__DIR__ . '/setup.sql');
    if ($sql === false || trim($sql) === '') {
        throw new RuntimeException('File database/setup.sql tidak dapat dibaca.');
    }
    $quotedDatabase = '`' . $database . '`';
    $sql = preg_replace(
        '/^CREATE DATABASE IF NOT EXISTS\s+`?[^`\s;]+`?/mi',
        'CREATE DATABASE IF NOT EXISTS ' . $quotedDatabase,
        $sql,
        1
    );
    $sql = preg_replace('/^USE\s+`?[^`\s;]+`?\s*;/mi', 'USE ' . $quotedDatabase . ';', $sql, 1);
    $pdo->exec($sql);
    $pdo->exec("USE {$quotedDatabase}");

    $adminCount = (int)$pdo->query('SELECT COUNT(*) FROM admin_users')->fetchColumn();
    if ($adminCount === 0) {
        $initialPassword = getenv('SILAKOM_INITIAL_ADMIN_PASSWORD');
        if ($initialPassword === false || strlen($initialPassword) < 16) {
            throw new RuntimeException(
                'Atur SILAKOM_INITIAL_ADMIN_PASSWORD dengan minimal 16 karakter untuk admin pertama.'
            );
        }

        $stmt = $pdo->prepare(
            "INSERT INTO admin_users (username, password, nama, role, is_active)
            VALUES (?, ?, ?, 'admin', 1)"
        );
        $stmt->execute(['admin', password_hash($initialPassword, PASSWORD_DEFAULT), 'Administrator BKHIT']);
    }

    echo "SUCCESS: Database {$database} berhasil dibuat & disiapkan.\n";
    echo $adminCount === 0
        ? "Admin awal dibuat. Simpan password yang diberikan melalui environment secara aman.\n"
        : "Akun admin yang sudah ada tidak diubah.\n";
} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
