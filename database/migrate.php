<?php
// Skrip inisialisasi database & seed
$host = '127.0.0.1';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host={$host};charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sql = file_get_contents(__DIR__ . '/setup.sql');
    $pdo->exec($sql);
    $pdo->exec("USE db_ekspor_karantina");

    // Pastikan user admin default ada
    $hash = password_hash('admin123', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO admin_users (username, password, nama) 
        VALUES ('admin', ?, 'Administrator BKHIT') 
        ON DUPLICATE KEY UPDATE password = ?, nama = 'Administrator BKHIT'");
    $stmt->execute([$hash, $hash]);

    echo "SUCCESS: Database db_ekspor_karantina berhasil dibuat & disiapkan!\n";
    echo "Default Admin: username = admin, password = admin123\n";
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
