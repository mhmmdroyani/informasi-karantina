<?php
// database/reset.php - Reset database dari nol
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Skrip ini hanya boleh dijalankan melalui CLI.\n");
}

$database = getenv('SILAKOM_DB_NAME') ?: 'db_ekspor_karantina';
$host = getenv('SILAKOM_DB_HOST') ?: '127.0.0.1';
$user = getenv('SILAKOM_DB_USER') ?: 'root';
$pass = getenv('SILAKOM_DB_PASSWORD');
$pass = $pass === false ? '' : $pass;
$initialPassword = getenv('SILAKOM_INITIAL_ADMIN_PASSWORD');
if (!preg_match('/^[A-Za-z0-9_]+$/', $database)) {
    fwrite(STDERR, "Nama database SILAKOM tidak valid.\n");
    exit(1);
}
if ($initialPassword === false || strlen($initialPassword) < 16) {
    fwrite(STDERR, "Atur SILAKOM_INITIAL_ADMIN_PASSWORD dengan minimal 16 karakter.\n");
    exit(1);
}

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
    $quotedDatabase = '`' . $database . '`';
    $schema = preg_replace(
        '/^CREATE DATABASE IF NOT EXISTS\s+`?[^`\s;]+`?/mi',
        'CREATE DATABASE IF NOT EXISTS ' . $quotedDatabase,
        $schema,
        1
    );
    $schema = preg_replace('/^USE\s+`?[^`\s;]+`?\s*;/mi', 'USE ' . $quotedDatabase . ';', $schema, 1);
    $pdo->exec($schema);
    $pdo->exec("USE {$quotedDatabase}");

    $adminHash = password_hash($initialPassword, PASSWORD_DEFAULT);
    $adminStmt = $pdo->prepare(
        "INSERT INTO admin_users (username, password, nama, role, is_active)
        VALUES (?, ?, ?, 'admin', 1)"
    );
    $adminStmt->execute(['admin', $adminHash, 'Administrator BKHIT']);

    echo "Reset database berhasil.\n";
    echo "Database: {$database}\n";
    echo "Akun admin dibuat. Gunakan password yang diberikan melalui environment secara aman.\n";
} catch (Throwable $e) {
    fwrite(STDERR, "Reset database gagal: {$e->getMessage()}\n");
    exit(1);
}
