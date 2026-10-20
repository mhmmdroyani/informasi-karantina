<?php
// admin/proses_update.php
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/input_validation.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: kelola_data.php');
    exit;
}
require_csrf();

$jenis = $_POST['jenis_kegiatan'] ?? 'domestik_masuk';
if (!in_array($jenis, ['ekspor', 'impor', 'domestik_keluar', 'domestik_masuk'])) {
    $jenis = 'domestik_masuk';
}

$kategori = $_POST['kategori'] ?? 'hewan';
if (!in_array($kategori, ['hewan', 'ikan', 'tumbuhan'])) {
    $kategori = 'hewan';
}

$periode = is_string($_POST['periode'] ?? null) ? trim($_POST['periode']) : '';

$topN = filter_var($_POST['top_n'] ?? 5, FILTER_VALIDATE_INT);
if (!in_array($topN, [0, 1, 3, 5, 10], true)) {
    $topN = 5;
}
$urutkan = in_array($_POST['urutkan_berdasarkan'] ?? '', ['nilai_ekspor', 'frekuensi']) ? $_POST['urutkan_berdasarkan'] : 'nilai_ekspor';

// Parse Kalender Rentang (hanya mode per Bulan yang didukung)
$filterMode = 'bulan';
$cal = parse_calendar_filter($filterMode, array_filter($_POST, 'is_scalar'));

$periodeLabel = is_string($_POST['periode_label'] ?? null) ? trim($_POST['periode_label']) : '';
if (empty($periodeLabel)) {
    $periodeLabel = $cal['label_tahun'];
} else {
    // Pastikan berformat TAHUN jika belum
    if (stripos($periodeLabel, 'tahun') === false && preg_match('/\b(20\d\d)\b/', $periodeLabel)) {
        $periodeLabel = format_label_tahun($cal['tgl_awal'], $cal['tgl_akhir'], $periodeLabel);
    }
}

$allIds = parse_positive_integer_ids($_POST['all_ids'] ?? []);
$submittedPublishedIds = parse_positive_integer_ids($_POST['published_ids'] ?? []);
$publishedIds = array_values(array_intersect($submittedPublishedIds, $allIds));
$searchQuery = is_string($_POST['q'] ?? null) ? trim($_POST['q']) : '';

try {
    $pdo->beginTransaction();

    // 1. Simpan Pengaturan Tampilan ke ekspor_settings per (jenis_kegiatan, kategori)
    $stmtSetting = $pdo->prepare("INSERT INTO ekspor_settings 
        (jenis_kegiatan, kategori, top_n, urutkan_berdasarkan, filter_mode, tanggal_awal, tanggal_akhir, tahun_awal, tahun_akhir, periode, periode_label) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) 
        ON DUPLICATE KEY UPDATE 
            top_n = VALUES(top_n), 
            urutkan_berdasarkan = VALUES(urutkan_berdasarkan), 
            filter_mode = VALUES(filter_mode),
            tanggal_awal = VALUES(tanggal_awal),
            tanggal_akhir = VALUES(tanggal_akhir),
            tahun_awal = VALUES(tahun_awal),
            tahun_akhir = VALUES(tahun_akhir),
            periode = VALUES(periode),
            periode_label = VALUES(periode_label)");
    $stmtSetting->execute([
        $jenis, 
        $kategori, 
        $topN, 
        $urutkan, 
        $cal['mode'], 
        $cal['tgl_awal'], 
        $cal['tgl_akhir'], 
        $cal['thn_awal'], 
        $cal['thn_akhir'],
        $periode,
        $periodeLabel
    ]);

    // 2. Update status is_published pada data yang sedang dikelola
    if (!empty($allIds)) {
        // Reset semua data dalam view ini menjadi is_published = 0
        $inPlaceholders = implode(',', array_fill(0, count($allIds), '?'));
        $stmtReset = $pdo->prepare("UPDATE ekspor_komoditas SET is_published = 0 WHERE jenis_kegiatan = ? AND kategori = ? AND periode = ? AND id IN ($inPlaceholders)");
        $stmtReset->execute(array_merge([$jenis, $kategori, $periode], $allIds));

        // Jika ada yang dicentang, set is_published = 1
        if (!empty($publishedIds)) {
            $inPub = implode(',', array_fill(0, count($publishedIds), '?'));
            $stmtPub = $pdo->prepare("UPDATE ekspor_komoditas SET is_published = 1 WHERE jenis_kegiatan = ? AND kategori = ? AND periode = ? AND id IN ($inPub)");
            $stmtPub->execute(array_merge([$jenis, $kategori, $periode], $publishedIds));
        }
    }

    $pdo->commit();

    $publishedCount = count($publishedIds);
    $redirectParams = [
        'jenis' => $jenis,
        'kategori' => $kategori,
        'periode' => $periode,
        'q' => $searchQuery,
        'msg' => 'sukses',
        'pub' => $publishedCount,
        'filter_mode' => $cal['mode'],
        'thn_awal' => $cal['thn_awal'],
        'thn_akhir' => $cal['thn_akhir'],
        'bln_awal' => $cal['bln_awal'],
        'bln_akhir' => $cal['bln_akhir'],
        'tgl_awal' => $cal['tgl_awal'],
        'tgl_akhir' => $cal['tgl_akhir']
    ];
    header("Location: kelola_data.php?" . http_build_query($redirectParams));
    exit;

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('SILAKOM update failed: ' . $e->getMessage());
    header("Location: kelola_data.php?jenis=" . urlencode($jenis) . "&kategori=" . urlencode($kategori) . "&error=" . urlencode('Pengaturan atau status publikasi gagal disimpan.'));
    exit;
}
