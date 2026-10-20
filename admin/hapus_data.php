<?php
// admin/hapus_data.php
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../config/auth.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Metode tidak diizinkan.');
}
require_csrf();

$action = $_POST['action'] ?? 'single';

if ($action === 'batch_ids') {
    $jenis = $_POST['jenis_kegiatan'] ?? 'domestik_masuk';
    if (!in_array($jenis, ['ekspor', 'impor', 'domestik_keluar', 'domestik_masuk'])) {
        $jenis = 'domestik_masuk';
    }
    $kategori = $_POST['kategori'] ?? 'hewan';
    if (!in_array($kategori, ['hewan', 'ikan', 'tumbuhan'])) {
        $kategori = 'hewan';
    }

    $ids = isset($_POST['published_ids']) && is_array($_POST['published_ids']) ? array_map('intval', $_POST['published_ids']) : [];
    if (!empty($ids)) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare("DELETE FROM ekspor_komoditas WHERE id IN ($placeholders)");
        $stmt->execute($ids);
    }

    header("Location: kelola_data.php?jenis=" . urlencode($jenis) . "&kategori=" . urlencode($kategori) . "&msg=hapus_sukses");
    exit;
}

$jenis = $_POST['jenis'] ?? $_POST['jenis_kegiatan'] ?? 'domestik_masuk';
if (!in_array($jenis, ['ekspor', 'impor', 'domestik_keluar', 'domestik_masuk'])) {
    $jenis = 'domestik_masuk';
}

$kategori = $_POST['kategori'] ?? 'hewan';
if (!in_array($kategori, ['hewan', 'ikan', 'tumbuhan'])) {
    $kategori = 'hewan';
}

$periode = $_POST['periode'] ?? '';

if ($action === 'single') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
        $stmt = $pdo->prepare("DELETE FROM ekspor_komoditas WHERE id = ?");
        $stmt->execute([$id]);
    }
} elseif ($action === 'batch_periode' && !empty($periode)) {
    $stmt = $pdo->prepare("DELETE FROM ekspor_komoditas WHERE jenis_kegiatan = ? AND kategori = ? AND periode = ?");
    $stmt->execute([$jenis, $kategori, $periode]);
} elseif ($action === 'all_kategori') {
    $stmt = $pdo->prepare("DELETE FROM ekspor_komoditas WHERE jenis_kegiatan = ? AND kategori = ?");
    $stmt->execute([$jenis, $kategori]);
}

$redirect = "kelola_data.php?jenis=" . urlencode($jenis) . "&kategori=" . urlencode($kategori);
if (!empty($periode) && $action !== 'batch_periode') {
    $redirect .= "&periode=" . urlencode($periode);
}
header("Location: " . $redirect . "&msg=hapus_sukses");
exit;
