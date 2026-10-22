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

    $redirectParams = [
        'jenis' => $jenis,
        'kategori' => $kategori,
        'periode_a' => trim($_POST['periode_a'] ?? ''),
        'periode_b' => trim($_POST['periode_b'] ?? ''),
        'msg' => 'hapus_sukses',
    ];
    header('Location: kelola_data.php?' . http_build_query($redirectParams));
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
$periodeA = trim($_POST['periode_a'] ?? $periode);
$periodeB = trim($_POST['periode_b'] ?? $periode);

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

$redirectParams = [
    'jenis' => $jenis,
    'kategori' => $kategori,
    'periode_a' => $periodeA,
    'periode_b' => $periodeB,
    'msg' => 'hapus_sukses',
];
header('Location: kelola_data.php?' . http_build_query($redirectParams));
exit;
