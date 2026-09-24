<?php
// config/auth.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function is_logged_in() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

function require_admin() {
    if (!is_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

function current_admin() {
    return [
        'id' => $_SESSION['admin_id'] ?? null,
        'username' => $_SESSION['admin_username'] ?? 'Admin',
        'nama' => $_SESSION['admin_nama'] ?? 'Administrator',
        'role' => $_SESSION['admin_role'] ?? 'admin',
    ];
}

function is_admin_role() {
    return ($_SESSION['admin_role'] ?? 'admin') === 'admin';
}

function require_role_admin() {
    require_admin();
    if (!is_admin_role()) {
        header('Location: index.php?error=' . urlencode('Anda tidak memiliki hak akses ke halaman ini.'));
        exit;
    }
}

function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

function require_csrf() {
    $submitted = $_POST['csrf_token'] ?? '';
    $stored = $_SESSION['csrf_token'] ?? '';
    if (!$stored || !$submitted || !hash_equals($stored, $submitted)) {
        http_response_code(403);
        exit('Permintaan tidak valid. Silakan muat ulang halaman dan coba lagi.');
    }
}
