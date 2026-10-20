<?php
// admin/hapus_data.php
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/input_validation.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Metode tidak diizinkan.');
}
require_csrf();

$action = $_POST['action'] ?? 'single';
$searchQuery = is_string($_POST['q'] ?? null) ? trim($_POST['q']) : '';

if ($action === 'batch_ids') {
    $jenis = $_POST['jenis_kegiatan'] ?? 'domestik_masuk';
    if (!in_array($jenis, ['ekspor', 'impor', 'domestik_keluar', 'domestik_masuk'])) {
        $jenis = 'domestik_masuk';
    }
    $kategori = $_POST['kategori'] ?? 'hewan';
    if (!in_array($kategori, ['hewan', 'ikan', 'tumbuhan'])) {
        $kategori = 'hewan';
    }

    $periode = is_string($_POST['periode'] ?? null) ? trim($_POST['periode']) : '';
    $ids = parse_positive_integer_ids($_POST['published_ids'] ?? []);
    $deleted = 0;
    if (!empty($ids) && $periode !== '') {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare("DELETE FROM ekspor_komoditas WHERE jenis_kegiatan = ? AND kategori = ? AND periode = ? AND id IN ($placeholders)");
        $stmt->execute(array_merge([$jenis, $kategori, $periode], $ids));
        $deleted = $stmt->rowCount();
    }

    $result = $deleted > 0 ? 'msg=hapus_sukses' : 'error=' . urlencode('Tidak ada data yang sesuai untuk dihapus.');
    header("Location: kelola_data.php?jenis=" . urlencode($jenis) . "&kategori=" . urlencode($kategori) . ($periode !== '' ? '&periode=' . urlencode($periode) : '') . ($searchQuery !== '' ? '&q=' . urlencode($searchQuery) : '') . "&" . $result);
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

$periode = is_string($_POST['periode'] ?? null) ? trim($_POST['periode']) : '';
$deleted = 0;

if ($action === 'single') {
    $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
    if ($id !== false && $id > 0 && $periode !== '') {
        $stmt = $pdo->prepare("DELETE FROM ekspor_komoditas WHERE id = ? AND jenis_kegiatan = ? AND kategori = ? AND periode = ?");
        $stmt->execute([$id, $jenis, $kategori, $periode]);
        $deleted = $stmt->rowCount();
    } else {
        http_response_code(400);
        exit('Aksi penghapusan tidak valid.');
    }
} elseif ($action === 'batch_periode' && !empty($periode)) {
    $stmt = $pdo->prepare("DELETE FROM ekspor_komoditas WHERE jenis_kegiatan = ? AND kategori = ? AND periode = ?");
    $stmt->execute([$jenis, $kategori, $periode]);
    $deleted = $stmt->rowCount();
} elseif ($action === 'all_kategori') {
    $stmt = $pdo->prepare("DELETE FROM ekspor_komoditas WHERE jenis_kegiatan = ? AND kategori = ?");
    $stmt->execute([$jenis, $kategori]);
    $deleted = $stmt->rowCount();
} else {
    http_response_code(400);
    exit('Aksi penghapusan tidak valid.');
}

$redirect = "kelola_data.php?jenis=" . urlencode($jenis) . "&kategori=" . urlencode($kategori);
if (!empty($periode) && $action !== 'batch_periode') {
    $redirect .= "&periode=" . urlencode($periode);
}
if ($searchQuery !== '') {
    $redirect .= '&q=' . urlencode($searchQuery);
}
$result = $deleted > 0 ? 'msg=hapus_sukses' : 'error=' . urlencode('Tidak ada data yang sesuai untuk dihapus.');
header("Location: " . $redirect . "&" . $result);
exit;
