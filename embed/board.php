<?php
// embed/board.php - Master Template Board Infografis Karantina Kalsel (Format PPT Canva)
require_once __DIR__ . '/../config/koneksi.php';

$assetPath = $assetPath ?? '';
$showControls = $show_controls ?? (isset($_GET['nav']) && $_GET['nav'] == 1);
$comparisonPage = $comparisonPage ?? false;

$kategori = $_GET['kategori'] ?? 'hewan';
if (!in_array($kategori, ['hewan', 'ikan', 'tumbuhan'])) {
    $kategori = 'hewan';
}

$jenis = $_GET['jenis'] ?? ($_GET['kegiatan'] ?? 'domestik_masuk');
if (!in_array($jenis, ['ekspor', 'impor', 'domestik_keluar', 'domestik_masuk'])) {
    $jenis = 'domestik_masuk';
}

// 1. Ambil Setting
$stmtSet = $pdo->prepare("SELECT * FROM ekspor_settings WHERE jenis_kegiatan = ? AND kategori = ? LIMIT 1");
$stmtSet->execute([$jenis, $kategori]);
$setting = $stmtSet->fetch() ?: [
    'top_n' => 5,
    'urutkan_berdasarkan' => 'nilai_ekspor',
    'filter_mode' => 'tahun',
    'tanggal_awal' => '2026-01-01',
    'tanggal_akhir' => '2026-12-31',
    'periode_label' => 'TAHUN 2026'
];

$topN = (int)($setting['top_n'] ?? 5);
$urutan = ($setting['urutkan_berdasarkan'] === 'frekuensi') ? 'frekuensi' : 'nilai_ekspor';
$periodeFilter = trim($_GET['periode'] ?? '');
$periodeStmt = $pdo->prepare("SELECT DISTINCT periode FROM ekspor_komoditas WHERE jenis_kegiatan = ? AND kategori = ? AND is_published = 1 ORDER BY periode ASC");
$periodeStmt->execute([$jenis, $kategori]);
$periodeOptions = $periodeStmt->fetchAll(PDO::FETCH_COLUMN);
if ($periodeFilter !== '' && !in_array($periodeFilter, $periodeOptions, true)) {
    $periodeFilter = !empty($periodeOptions) ? end($periodeOptions) : '';
}
if ($periodeFilter !== '') {
    $stmtPeriodSet = $pdo->prepare("SELECT * FROM ekspor_settings WHERE jenis_kegiatan = ? AND kategori = ? AND periode = ? LIMIT 1");
    $stmtPeriodSet->execute([$jenis, $kategori, $periodeFilter]);
    $periodSetting = $stmtPeriodSet->fetch();
    if ($periodSetting) {
        $setting = array_merge($setting, $periodSetting);
        $topN = (int)($setting['top_n'] ?? 5);
        $urutan = ($setting['urutkan_berdasarkan'] === 'frekuensi') ? 'frekuensi' : 'nilai_ekspor';
    }
}
$compareMode = $comparisonPage || (isset($_GET['banding']) && $_GET['banding'] === '1');
$periodeA = trim($_GET['periode_a'] ?? ($periodeOptions[0] ?? ''));
$periodeB = trim($_GET['periode_b'] ?? (count($periodeOptions) > 1 ? end($periodeOptions) : ($periodeOptions[0] ?? '')));
if (!in_array($periodeA, $periodeOptions, true)) $periodeA = $periodeOptions[0] ?? '';
if (!in_array($periodeB, $periodeOptions, true)) $periodeB = $periodeOptions[count($periodeOptions) - 1] ?? '';
$comparisonLabelA = format_label_periode_kode($periodeA) ?: $periodeA;
$comparisonLabelB = format_label_periode_kode($periodeB) ?: $periodeB;

$comparison = null;
if ($compareMode && $periodeA !== '' && $periodeB !== '') {
    $compareStmt = $pdo->prepare("SELECT periode, nilai_ekspor, volume, frekuensi, negara_tujuan FROM ekspor_komoditas WHERE jenis_kegiatan = ? AND kategori = ? AND is_published = 1 AND periode IN (?, ?)");
    $compareStmt->execute([$jenis, $kategori, $periodeA, $periodeB]);
    $comparisonRows = $compareStmt->fetchAll();
    $comparison = [];
    foreach ([$periodeA, $periodeB] as $period) {
        $rowsForPeriod = array_filter($comparisonRows, fn($row) => $row['periode'] === $period);
        $countries = [];
        foreach ($rowsForPeriod as $row) {
            $parts = preg_split('/\s*(?:,|;|\/|\|)\s*/', (string)($row['negara_tujuan'] ?? ''));
            foreach ($parts as $country) {
                $country = trim($country);
                if ($country !== '') $countries[strtolower($country)] = $country;
            }
        }
        $comparison[$period] = [
            'nilai' => (float)array_sum(array_column($rowsForPeriod, 'nilai_ekspor')),
            'volume' => (float)array_sum(array_column($rowsForPeriod, 'volume')),
            'frekuensi' => (int)array_sum(array_column($rowsForPeriod, 'frekuensi')),
            'negara' => count($countries)
        ];
    }
}

// Filter kalender: utamakan parameter URL jika ada, jika tidak gunakan setting tersimpan
if (isset($_GET['bln_awal']) || isset($_GET['thn_awal']) || isset($_GET['tahun_awal']) || isset($_GET['tgl_awal']) || isset($_GET['mode'])) {
    $cal = parse_calendar_filter('bulan', $_GET);
    $tglAwal = $cal['tgl_awal'];
    $tglAkhir = $cal['tgl_akhir'];
    $labelTahunPublik = $cal['label_tahun'];
} else {
    $tglAwal = $setting['tanggal_awal'] ?? null;
    $tglAkhir = $setting['tanggal_akhir'] ?? null;
    // Selalu hitung ulang dari rentang tanggal tersimpan agar selalu sinkron dengan bulan yang dipilih di Kelola Data
    $labelTahunPublik = format_label_tahun($tglAwal, $tglAkhir, '');
}

if ($periodeFilter !== '') {
    $labelPeriodeFilter = format_label_periode_kode($periodeFilter);
    if ($labelPeriodeFilter !== '') {
        $labelTahunPublik = $labelPeriodeFilter;
    }
} else {
    $labelTahunPublik = $compareMode && $periodeA !== '' && $periodeB !== ''
        ? $comparisonLabelA . ' vs ' . $comparisonLabelB
        : 'PILIH PERIODE DATA';
}

// 2. Query Data Published
$queryParams = [$jenis, $kategori];
$sql = "SELECT * FROM ekspor_komoditas WHERE jenis_kegiatan = ? AND kategori = ? AND is_published = 1";
if ($periodeFilter !== '') {
    $sql .= " AND periode = ?";
    $queryParams[] = $periodeFilter;
} else {
    $sql .= " AND 1 = 0";
}
$sql .= " ORDER BY {$urutan} DESC";
$stmtData = $pdo->prepare($sql);
$stmtData->execute($queryParams);
$allData = $stmtData->fetchAll();

// Batasi tampilan Top N, tetapi tetap gunakan seluruh dataset yang terpilih untuk agregat KPI.
$data = $allData;
if ($topN > 0 && count($data) > $topN) {
    $data = array_slice($data, 0, $topN);
}

// 3. Hitung Metrik dari seluruh data yang dipilih, bukan hanya baris yang ditampilkan
$totalNilai = (float)array_sum(array_column($allData, 'nilai_ekspor'));
$totalFrekuensi = (int)array_sum(array_column($allData, 'frekuensi'));
$volumeByUnit = [];
foreach ($allData as $row) {
    $unit = trim((string)($row['satuan'] ?? '')) ?: 'Kilogram';
    $volumeByUnit[$unit] = ($volumeByUnit[$unit] ?? 0) + (float)$row['volume'];
}
arsort($volumeByUnit);
$primaryVolume = (float)(reset($volumeByUnit) ?: 0);
$primaryVolumeUnit = (string)(key($volumeByUnit) ?: 'Kilogram');
$secondaryVolumes = array_slice($volumeByUnit, 1, null, true);

$kpiNilai = format_kpi_nilai($totalNilai);

// Arah & Wilayah
$countryAliases = [
    'china' => 'China', 'tiongkok' => 'China',
    'hong kong' => 'Hong Kong', 'hongkong' => 'Hong Kong',
    'taiwan' => 'Taiwan',
    'singapura' => 'Singapura', 'singapore' => 'Singapura',
    'jepang' => 'Jepang', 'japan' => 'Jepang',
    'malaysia' => 'Malaysia',
    'vietnam' => 'Vietnam',
    'thailand' => 'Thailand',
    'india' => 'India',
    'korea selatan' => 'Korea Selatan', 'south korea' => 'Korea Selatan', 'korea' => 'Korea Selatan',
    'filipina' => 'Filipina', 'philippines' => 'Filipina', 'phillipina' => 'Filipina', 'philipina' => 'Filipina',
    'brunei' => 'Brunei', 'brunei darussalam' => 'Brunei',
    'amerika serikat' => 'Amerika Serikat', 'united states' => 'Amerika Serikat', 'usa' => 'Amerika Serikat', 'america' => 'Amerika Serikat',
    'kanada' => 'Kanada', 'canada' => 'Kanada',
    'australia' => 'Australia',
    'selandia baru' => 'Selandia Baru', 'new zealand' => 'Selandia Baru',
    'belanda' => 'Belanda', 'netherlands' => 'Belanda',
    'jerman' => 'Jerman', 'germany' => 'Jerman',
    'italia' => 'Italia', 'italy' => 'Italia',
    'prancis' => 'Prancis', 'france' => 'Prancis',
    'inggris' => 'Inggris', 'united kingdom' => 'Inggris',
    'spanyol' => 'Spanyol', 'spain' => 'Spanyol',
    'rusia' => 'Rusia', 'russia' => 'Rusia',
    'uni emirat arab' => 'Uni Emirat Arab', 'united arab emirates' => 'Uni Emirat Arab', 'uae' => 'Uni Emirat Arab',
    'arab saudi' => 'Arab Saudi', 'saudi arabia' => 'Arab Saudi',
    'qatar' => 'Qatar',
    'bangladesh' => 'Bangladesh', 'banglades' => 'Bangladesh',
    'turki' => 'Turki', 'turkey' => 'Turki',
    'mesir' => 'Mesir', 'egypt' => 'Mesir',
    'afrika selatan' => 'Afrika Selatan', 'south africa' => 'Afrika Selatan',
    'brasil' => 'Brasil', 'brazil' => 'Brasil',
    'chile' => 'Chile',
    'meksiko' => 'Meksiko', 'mexico' => 'Meksiko'
];

$normalizeCountryName = function ($name) use ($countryAliases) {
    $value = trim((string)$name);
    $value = preg_replace('/\s+/', ' ', $value);
    $value = preg_replace('/[;|\/]+/', ',', $value);
    $normalized = strtolower($value);
    foreach ($countryAliases as $alias => $canonical) {
        if ($normalized === strtolower($alias)) {
            return $canonical;
        }
    }
    $normalized = preg_replace('/[^a-z0-9 ,\-]/i', '', $normalized);
    $normalized = preg_replace('/\s+/', ' ', trim($normalized));
    return ucwords(strtolower($normalized));
};

$uniqueDest = [];
$destValues = [];
foreach ($allData as $r) {
    if (!empty($r['negara_tujuan'])) {
        $parts = preg_split('/\s*(?:,|;|\/|\|)\s*/', $r['negara_tujuan']);
        $validParts = array_values(array_filter($parts, fn($x) => trim((string)$x) !== ''));
        foreach ($validParts as $p) {
            $countryName = $normalizeCountryName($p);
            if ($countryName === '') {
                continue;
            }
            $countryKey = strtolower($countryName);
            $uniqueDest[$countryKey] = $countryName;
            $destValues[$countryKey] = ($destValues[$countryKey] ?? 0) + (float)$r['nilai_ekspor'] / max(1, count($validParts));
        }
    }
}
$daftarDest = array_values($uniqueDest);
$jumlahWilayah = count($daftarDest);

// Subteks Wilayah
$isDomestik = in_array($jenis, ['domestik_keluar', 'domestik_masuk']);
$countryFlagCodes = [
    'china' => 'cn', 'tiongkok' => 'cn', 'hong kong' => 'hk', 'taiwan' => 'tw',
    'singapura' => 'sg', 'singapore' => 'sg', 'jepang' => 'jp', 'japan' => 'jp',
    'malaysia' => 'my', 'vietnam' => 'vn', 'thailand' => 'th', 'india' => 'in',
    'korea selatan' => 'kr', 'south korea' => 'kr', 'filipina' => 'ph',
        'philippines' => 'ph', 'phillipina' => 'ph', 'philipina' => 'ph', 'brunei' => 'bn', 'brunei darussalam' => 'bn',
    'amerika serikat' => 'us', 'united states' => 'us', 'usa' => 'us',
    'kanada' => 'ca', 'canada' => 'ca', 'australia' => 'au',
    'selandia baru' => 'nz', 'new zealand' => 'nz', 'belanda' => 'nl',
    'netherlands' => 'nl', 'jerman' => 'de', 'germany' => 'de',
    'italia' => 'it', 'italy' => 'it', 'prancis' => 'fr', 'france' => 'fr',
    'inggris' => 'gb', 'united kingdom' => 'gb', 'spanyol' => 'es', 'spain' => 'es',
    'rusia' => 'ru', 'russia' => 'ru', 'uni emirat arab' => 'ae', 'uni emirad arab' => 'ae',
    'united arab emirates' => 'ae', 'arab saudi' => 'sa', 'saudi arabia' => 'sa',
    'qatar' => 'qa', 'bangladesh' => 'bd', 'banglades' => 'bd',
    'turki' => 'tr', 'turkey' => 'tr', 'mesir' => 'eg',
    'egypt' => 'eg', 'afrika selatan' => 'za', 'south africa' => 'za',
    'brasil' => 'br', 'brazil' => 'br', 'chile' => 'cl', 'meksiko' => 'mx',
    'mexico' => 'mx', 'venezuela' => 've', 'argentina' => 'ar',
    'portugal' => 'pt', 'belgia' => 'be', 'belgium' => 'be',
    'kolombia' => 'co', 'colombia' => 'co', 'peru' => 'pe',
    'ecuador' => 'ec', 'panama' => 'pa',
    'new caledonia' => 'nc', 'papua nugini' => 'pg', 'papua new guinea' => 'pg'
];
if ($isDomestik) {
    $subWilayah = max(1, count($daftarDest)) . ' Provinsi/Area';
} else {
    $subWilayah = hitung_benua($daftarDest) . ' Benua';
}

arsort($destValues);

// Labels
$labelKegiatan = get_label_kegiatan($jenis);
$labelKategori = ucfirst($kategori);
$labelArah = get_label_arah($jenis);
$labelArahUpper = $labelArah;
$labelSatuanWilayah = get_label_wilayah($jenis);
$labelDokumen = get_label_sertifikat($jenis, $kategori);

// Top Commodity Name
$topKomoditasName = 'Komoditas';
if (!empty($data)) {
    if ($kategori === 'ikan' && !empty($data[0]['nama_komoditas_lokal'])) {
        $topKomoditasName = $data[0]['nama_komoditas_lokal'];
    } else {
        $topKomoditasName = $data[0]['nama_komoditas'];
    }
}

// Chart Colors
$colors = ['#0f766e', '#2563eb', '#b45309', '#64748b', '#7c3aed'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $labelKegiatan ?> Komoditas Karantina <?= $labelKategori ?> - BKHIT Kalsel</title>
    <link rel="stylesheet" href="<?= $assetPath ?>embed.css?v=<?= @filemtime(__DIR__ . '/embed.css') ?: time() ?>">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body>

<?php
$urlParamsExtra = '';
if (isset($_GET['thn_awal'])) $urlParamsExtra .= '&thn_awal=' . urlencode($_GET['thn_awal']);
if (isset($_GET['thn_akhir'])) $urlParamsExtra .= '&thn_akhir=' . urlencode($_GET['thn_akhir']);
if (isset($_GET['bln_awal'])) $urlParamsExtra .= '&bln_awal=' . urlencode($_GET['bln_awal']);
if (isset($_GET['bln_akhir'])) $urlParamsExtra .= '&bln_akhir=' . urlencode($_GET['bln_akhir']);
if (isset($_GET['tgl_awal'])) $urlParamsExtra .= '&tgl_awal=' . urlencode($_GET['tgl_awal']);
if (isset($_GET['tgl_akhir'])) $urlParamsExtra .= '&tgl_akhir=' . urlencode($_GET['tgl_akhir']);
if (isset($_GET['mode'])) $urlParamsExtra .= '&mode=' . urlencode($_GET['mode']);
$urlParamsBase = $urlParamsExtra;
if ($periodeFilter !== '') $urlParamsExtra .= '&periode=' . urlencode($periodeFilter);
?>

<!-- Top Bar -->
<header class="board-topbar">
    <div class="board-topbar-inner">
        <div class="header-left">
            <img src="<?= $assetPath ?>logo-barantin.png" alt="Logo Barantin" class="barantin-logo-img">
            <div class="header-unit-text">
                <span class="hut-karantina">Badan Karantina Indonesia</span>
                <span class="hut-kalsel">BKHIT Kalimantan Selatan</span>
            </div>
        </div>

        <?php if ($showControls): ?>
        <div class="board-topbar-actions">
            <a href="<?= $assetPath ?>../admin/index.php" class="board-login-btn">Login</a>
        </div>
        <?php endif; ?>
    </div>
</header>

<div class="board-container">

    <!-- Hero -->
    <section class="board-hero">
        <h1 class="board-hero-title"><?= $compareMode ? 'Perbandingan Komoditas' : 'Top ' . ($topN > 0 ? $topN : 'Semua') . ' Komoditas' ?> <?= $labelKategori ?> — <?= $labelKegiatan ?></h1>
        <p class="board-hero-desc">
            Ringkasan lalu lintas komoditas karantina yang dipublikasikan oleh Balai Karantina Hewan, Ikan, dan Tumbuhan Kalimantan Selatan.
        </p>

        <div class="board-periode-wrap">
            <span class="board-periode-badge"><?= htmlspecialchars(str_replace(' - ', ' – ', $labelTahunPublik)) ?></span>
                <button class="board-download-btn" type="button" onclick="openPrintBoard()">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9V3h12v6M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2M6 14h12v7H6z"/></svg>
                Cetak
                </button>
        </div>

        <?php if ($showControls): ?>
        <div class="board-hero-controls">
            <label class="hero-select-field">
                <span class="hero-filter-label">Lalu Lintas</span>
                <select name="jenis" id="selectJenis" onchange="window.location.href = this.value" aria-label="Pilih lalu lintas">
                    <option value="?jenis=domestik_masuk&kategori=<?= $kategori ?><?= $urlParamsExtra ?>" <?= $jenis === 'domestik_masuk' ? 'selected' : '' ?>>Domestik Masuk</option>
                    <option value="?jenis=domestik_keluar&kategori=<?= $kategori ?><?= $urlParamsExtra ?>" <?= $jenis === 'domestik_keluar' ? 'selected' : '' ?>>Domestik Keluar</option>
                    <option value="?jenis=ekspor&kategori=<?= $kategori ?><?= $urlParamsExtra ?>" <?= $jenis === 'ekspor' ? 'selected' : '' ?>>Ekspor</option>
                    <option value="?jenis=impor&kategori=<?= $kategori ?><?= $urlParamsExtra ?>" <?= $jenis === 'impor' ? 'selected' : '' ?>>Impor</option>
                </select>
            </label>

            <label class="hero-select-field">
                <span class="hero-filter-label">Komoditas</span>
                <select name="kategori" id="selectKategori" onchange="window.location.href = this.value" aria-label="Pilih komoditas">
                    <option value="?jenis=<?= $jenis ?>&kategori=hewan<?= $urlParamsExtra ?>" <?= $kategori === 'hewan' ? 'selected' : '' ?>>Hewan</option>
                    <option value="?jenis=<?= $jenis ?>&kategori=ikan<?= $urlParamsExtra ?>" <?= $kategori === 'ikan' ? 'selected' : '' ?>>Ikan</option>
                    <option value="?jenis=<?= $jenis ?>&kategori=tumbuhan<?= $urlParamsExtra ?>" <?= $kategori === 'tumbuhan' ? 'selected' : '' ?>>Tumbuhan</option>
                </select>
            </label>

            <?php if (!$comparisonPage): ?>
            <label class="hero-select-field">
                <span class="hero-filter-label">Periode Data</span>
                <select name="periode" id="selectPeriode" onchange="window.location.href = this.value" aria-label="Pilih periode data">
                    <option value="?jenis=<?= $jenis ?>&kategori=<?= $kategori ?><?= $urlParamsBase ?>" <?= $periodeFilter === '' ? 'selected' : '' ?> disabled><?= $compareMode ? htmlspecialchars($comparisonLabelA . ' vs ' . $comparisonLabelB) : 'Pilih periode data' ?></option>
                    <?php foreach ($periodeOptions as $periodeOption): ?>
                        <?php $periodeLabel = format_label_periode_kode($periodeOption) ?: $periodeOption; ?>
                        <option value="?jenis=<?= $jenis ?>&kategori=<?= $kategori ?>&periode=<?= urlencode($periodeOption) ?>" <?= $periodeFilter === $periodeOption ? 'selected' : '' ?>><?= htmlspecialchars($periodeLabel) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>

            <a href="<?= $assetPath ?>perbandingan.php?jenis=<?= urlencode($jenis) ?>&kategori=<?= urlencode($kategori) ?>" class="board-compare-btn">Bandingkan Periode</a>
            <?php else: ?>
            <a href="<?= $assetPath ?>../index.php?jenis=<?= urlencode($jenis) ?>&kategori=<?= urlencode($kategori) ?><?= $periodeFilter !== '' ? '&periode=' . urlencode($periodeFilter) : '' ?>" class="board-compare-btn">Kembali ke Board</a>
            <?php endif; ?>

        </div>
        <?php endif; ?>
    </section>

    <?php if ($compareMode && $comparison !== null): ?>
        <?php
        $comparisonMetrics = [
            ['label' => 'Nilai Ekspor', 'key' => 'nilai', 'format' => fn($value) => format_rupiah_singkat($value)],
            ['label' => 'Volume', 'key' => 'volume', 'format' => fn($value) => format_angka($value, 1)],
            ['label' => 'Frekuensi', 'key' => 'frekuensi', 'format' => fn($value) => format_angka($value)],
            ['label' => 'Negara Tujuan', 'key' => 'negara', 'format' => fn($value) => format_angka($value)]
        ];
        ?>
        <section class="board-comparison" aria-label="Perbandingan periode">
            <div class="board-comparison-header">
                <div>
                    <h2>Perbandingan Periode</h2>
                    <p>Bandingkan data yang sudah dipublikasikan untuk dua snapshot periode.</p>
                </div>
            </div>
            <form method="GET" class="board-comparison-filters">
                <input type="hidden" name="jenis" value="<?= htmlspecialchars($jenis) ?>">
                <input type="hidden" name="kategori" value="<?= htmlspecialchars($kategori) ?>">
                <input type="hidden" name="banding" value="1">
                <label>Periode A
                    <select name="periode_a" onchange="this.form.submit()">
                        <?php foreach ($periodeOptions as $period): ?>
                            <option value="<?= htmlspecialchars($period) ?>" <?= $periodeA === $period ? 'selected' : '' ?>><?= htmlspecialchars(format_label_periode_kode($period) ?: $period) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <span class="board-comparison-vs">vs</span>
                <label>Periode B
                    <select name="periode_b" onchange="this.form.submit()">
                        <?php foreach ($periodeOptions as $period): ?>
                            <option value="<?= htmlspecialchars($period) ?>" <?= $periodeB === $period ? 'selected' : '' ?>><?= htmlspecialchars(format_label_periode_kode($period) ?: $period) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </form>
            <div class="board-comparison-grid">
                <?php foreach ($comparisonMetrics as $metric):
                    $valueA = $comparison[$periodeA][$metric['key']];
                    $valueB = $comparison[$periodeB][$metric['key']];
                    $change = $valueA == 0 ? ($valueB == 0 ? 0 : null) : (($valueB - $valueA) / abs($valueA)) * 100;
                ?>
                    <div class="board-comparison-card">
                        <span><?= htmlspecialchars($metric['label']) ?></span>
                        <div class="comparison-values">
                            <div>
                                <small><?= htmlspecialchars($comparisonLabelA) ?></small>
                                <strong><?= $metric['format']($valueA) ?></strong>
                            </div>
                            <div>
                                <small><?= htmlspecialchars($comparisonLabelB) ?></small>
                                <strong><?= $metric['format']($valueB) ?></strong>
                            </div>
                        </div>
                        <em class="<?= $change !== null && $change < 0 ? 'is-down' : 'is-up' ?>"><?= $change === null ? 'Baru' : sprintf('%+.1f%%', $change) ?></em>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="board-comparison-chart">
                <h3>Grafik Perbandingan</h3>
                <div class="comparison-chart-grid">
                    <div class="comparison-chart-card"><span>Nilai Ekspor (Miliar Rp)</span><div class="comparison-chart-canvas"><canvas id="comparisonChartNilai"></canvas></div></div>
                    <div class="comparison-chart-card"><span>Volume</span><div class="comparison-chart-canvas"><canvas id="comparisonChartVolume"></canvas></div></div>
                    <div class="comparison-chart-card"><span>Frekuensi</span><div class="comparison-chart-canvas"><canvas id="comparisonChartFrekuensi"></canvas></div></div>
                    <div class="comparison-chart-card"><span>Negara Tujuan</span><div class="comparison-chart-canvas"><canvas id="comparisonChartNegara"></canvas></div></div>
                </div>
            </div>
            <script>
            const comparisonLabels = [<?= json_encode($comparisonLabelA) ?>, <?= json_encode($comparisonLabelB) ?>];
            const comparisonCharts = [
                ['comparisonChartNilai', [<?= $comparison[$periodeA]['nilai'] / 1000000000 ?>, <?= $comparison[$periodeB]['nilai'] / 1000000000 ?>]],
                ['comparisonChartVolume', [<?= $comparison[$periodeA]['volume'] ?>, <?= $comparison[$periodeB]['volume'] ?>]],
                ['comparisonChartFrekuensi', [<?= $comparison[$periodeA]['frekuensi'] ?>, <?= $comparison[$periodeB]['frekuensi'] ?>]],
                ['comparisonChartNegara', [<?= $comparison[$periodeA]['negara'] ?>, <?= $comparison[$periodeB]['negara'] ?>]]
            ];
            comparisonCharts.forEach(function(chartItem) {
                new Chart(document.getElementById(chartItem[0]), {
                    type: 'bar',
                    data: {
                        labels: comparisonLabels,
                        datasets: [{
                            data: chartItem[1],
                            backgroundColor: ['#2563eb', '#0f766e'],
                            borderRadius: 5,
                            barPercentage: 0.55
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: {
                            y: { beginAtZero: true, grid: { color: '#e5e7eb' } },
                            x: { grid: { display: false }, ticks: { font: { size: 9 } } }
                        }
                    }
                });
            });
            </script>
        </section>
    <?php endif; ?>

    <?php if (!$compareMode && empty($data)): ?>
        <div class="board-empty-state">
            <h3>Data Belum Dipublikasikan</h3>
            <p><?= $periodeFilter === '' ? 'Pilih periode data terlebih dahulu untuk menampilkan ringkasan.' : 'Belum ada data yang dipublikasikan untuk periode ini.' ?></p>
        </div>
    <?php elseif (!$compareMode): ?>

        <!-- Kartu Ringkasan -->
        <div class="kpi-row-canva">
            <!-- 1. Nilai -->
            <div class="kpi-card-canva">
                <div class="kpi-icon-circle blue">
                    <svg width="22" height="22" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2H4v2h1v11a3 3 0 003 3h8a3 3 0 003-3V8h1V6h-4zm-6-2h4v2h-4V4zm7 15a1 1 0 01-1 1H8a1 1 0 01-1-1V8h10v11z"/>
                        <path d="M9 10h2v2H9v1h2v1H9v1h3v-1h-1v-1h1a1 1 0 001-1v-1a1 1 0 00-1-1H9z"/>
                    </svg>
                </div>
                <div class="kpi-content-canva">
                    <div class="kpi-title-canva">Nilai <?= $labelKegiatan ?></div>
                    <div class="kpi-number-canva blue"><?= $kpiNilai['angka'] ?></div>
                    <div class="kpi-unit-canva blue"><?= $kpiNilai['satuan'] ?></div>
                </div>
            </div>

            <!-- 2. Volume -->
            <div class="kpi-card-canva">
                <div class="kpi-icon-circle teal">
                    <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"/>
                    </svg>
                </div>
                <div class="kpi-content-canva">
                    <div class="kpi-title-canva">Volume (Netto)</div>
                    <div class="kpi-number-canva teal"><?= format_angka($primaryVolume, 1) ?></div>
                    <div class="kpi-unit-canva teal"><?= htmlspecialchars($primaryVolumeUnit) ?></div>
                    <div class="kpi-volume-secondary">
                        <?php foreach ($secondaryVolumes as $unit => $volume): ?>
                            <span>dan <?= format_angka($volume, fmod((float)$volume, 1) === 0.0 ? 0 : 2) ?> <?= htmlspecialchars($unit) ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- 3. Frekuensi sertifikasi -->
            <div class="kpi-card-canva">
                <div class="kpi-icon-circle lime">
                    <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                    </svg>
                </div>
                <div class="kpi-content-canva">
                    <div class="kpi-title-canva">Frekuensi Sertifikasi</div>
                    <div class="kpi-number-canva lime"><?= format_angka($totalFrekuensi) ?></div>
                    <div class="kpi-unit-canva lime">Kali</div>
                    <div class="kpi-subtext-canva"><?= htmlspecialchars($labelDokumen) ?></div>
                </div>
            </div>

            <!-- 4. Negara / daerah -->
            <div class="kpi-card-canva">
                <div class="kpi-icon-circle orange">
                    <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="10"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2 12h20M12 2a15.3 15.3 0 014 10 15.3 15.3 0 01-4 10 15.3 15.3 0 01-4-10 15.3 15.3 0 014-10z"/>
                    </svg>
                </div>
                <div class="kpi-content-canva">
                    <div class="kpi-title-canva"><?= $labelArahUpper ?></div>
                    <div class="kpi-number-canva orange"><?= $jumlahWilayah ?></div>
                    <div class="kpi-unit-canva orange"><?= $labelSatuanWilayah ?></div>
                    <div class="kpi-subtext-canva"><?= $subWilayah ?></div>
                </div>
            </div>
        </div>

        <!-- Panel Ringkasan -->
        <div class="panels-row-canva">
            
            <!-- Panel 1: Kontribusi Nilai -->
            <div class="panel-canva">
                <div class="panel-header-canva">
                    <h3>Kontribusi Nilai <?= $labelKegiatan ?> berdasarkan Komoditas</h3>
                </div>
                <div class="panel-body-canva">
                    <div class="donut-layout">
                        <div class="donut-chart-box">
                            <canvas id="donutChartMaster"></canvas>
                            <div class="donut-center-overlay">
                                <span class="dco-label">Total</span>
                                <span class="dco-val"><?= $kpiNilai['angka'] ?></span>
                                <span class="dco-unit"><?= $kpiNilai['satuan'] ?></span>
                            </div>
                        </div>

                        <div class="donut-legend-list">
                            <?php foreach (array_slice($data, 0, $topN > 0 ? $topN : count($data)) as $idx => $row): 
                                $comName = ($kategori === 'ikan' && !empty($row['nama_komoditas_lokal'])) ? $row['nama_komoditas_lokal'] : $row['nama_komoditas'];
                                $pct = $totalNilai > 0 ? round(($row['nilai_ekspor'] / $totalNilai) * 100, 1) : 0;
                            ?>
                                <div class="legend-item-canva">
                                    <span class="legend-dot-canva" style="background-color: <?= $colors[$idx % count($colors)] ?>;"></span>
                                    <div class="legend-item-info">
                                        <div class="legend-com-name"><?= htmlspecialchars($comName) ?></div>
                                        <div class="legend-row">
                                            <span class="legend-val-badge"><?= format_rupiah_singkat($row['nilai_ekspor']) ?></span>
                                            <span class="legend-pct"><?= $pct ?>%</span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Sorotan Komoditas Teratas -->
                    <div class="panel-highlight-box">
                        <div class="ph-icon-box">
                            <?php if ($kategori === 'ikan'): ?>
                                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 12c-3 4-8 5-12 2l-4 4 1-5c-1.5-2-1.5-5 0-7l-1-5 4 4c4-3 9-2 12 2z"/>
                                </svg>
                            <?php elseif ($kategori === 'tumbuhan'): ?>
                                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9 9 0 009-9c0-4.97-4.03-9-9-9s-9 4.03-9 9a9 9 0 009 9zm0 0v-8m0 0l-3 3m3-3l3 3"/>
                                </svg>
                            <?php else: ?>
                                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                                </svg>
                            <?php endif; ?>
                        </div>
                        <div class="ph-text">
                            <strong><?= htmlspecialchars($topKomoditasName) ?></strong> mendominasi arus <?= strtolower($labelKegiatan) ?> komoditas <?= $kategori ?> di Kalimantan Selatan
                        </div>
                    </div>
                </div>
            </div>

            <!-- Panel 2: Top Komoditas -->
            <div class="panel-canva">
                <div class="panel-header-canva">
                    <h3>Top Komoditas berdasarkan Nilai <?= $labelKegiatan ?></h3>
                </div>
                <div class="panel-body-canva">
                    <div class="top-bar-items">
                        <?php foreach ($data as $i => $row): 
                            $comName = ($kategori === 'ikan' && !empty($row['nama_komoditas_lokal'])) ? $row['nama_komoditas_lokal'] : $row['nama_komoditas'];
                            $pct = $totalNilai > 0 ? round(($row['nilai_ekspor'] / $totalNilai) * 100, 1) : 0;
                        ?>
                            <div class="top-bar-row">
                                <span class="tbr-badge"><?= $i + 1 ?></span>
                                <span class="tbr-label"><?= htmlspecialchars($comName) ?></span>
                                <span class="tbr-val"><?= format_rupiah_singkat($row['nilai_ekspor']) ?></span>
                                <span class="tbr-pct"><?= $pct ?>%</span>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="bar-chart-container-canva">
                        <canvas id="barChartMaster"></canvas>
                    </div>
                </div>
            </div>

            <!-- Panel 3: Top Negara / Daerah -->
            <div class="panel-canva">
                <div class="panel-header-canva">
                    <h3>Top <?= $labelArahUpper ?></h3>
                </div>
                <div class="panel-body-canva">
                    <div class="map-canvas-container">
                        <div class="dest-list">
                            <?php 
                            $dIdx = 0;
                            foreach (array_slice($destValues, 0, $topN > 0 ? $topN : count($destValues), true) as $dName => $dVal):
                                $dPct = $totalNilai > 0 ? round(($dVal / $totalNilai) * 100, 0) : 0;
                                $flagCode = null;
                                if (!$isDomestik) {
                                    $destinationKey = strtolower(trim($dName));
                                    foreach ($countryFlagCodes as $countryName => $countryCode) {
                                        if ($destinationKey === $countryName || strpos($destinationKey, $countryName) !== false) {
                                            $flagCode = $countryCode;
                                            break;
                                        }
                                    }
                                }
                            ?>
                                <div class="dest-row" title="<?= htmlspecialchars($dName) ?>">
                                    <?php if ($flagCode): ?><img class="dest-flag" src="https://flagcdn.com/w40/<?= $flagCode ?>.png" alt="Bendera <?= htmlspecialchars($dName) ?>"><?php endif; ?>
                                    <?php if (!$flagCode): ?><span class="dest-dot" style="background-color: <?= $colors[$dIdx % count($colors)] ?>;"></span><?php endif; ?>
                                    <span class="dest-name"><?= htmlspecialchars(strtoupper($dName)) ?></span>
                                    <span class="dest-val"><?= format_rupiah_singkat($dVal) ?> · <?= $dPct ?>%</span>
                                </div>
                            <?php $dIdx++; endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <script>
        const rawLabels = <?= json_encode(array_map(function($r) use ($kategori) {
            return ($kategori === 'ikan' && !empty($r['nama_komoditas_lokal'])) ? $r['nama_komoditas_lokal'] : $r['nama_komoditas'];
        }, $data)) ?>;
        const rawValues = <?= json_encode(array_map('floatval', array_column($data, 'nilai_ekspor'))) ?>;
        const colors = <?= json_encode($colors) ?>;

        // 1. Donut Chart
        new Chart(document.getElementById('donutChartMaster'), {
            type: 'doughnut',
            data: {
                labels: rawLabels,
                datasets: [{
                    data: rawValues,
                    backgroundColor: colors,
                    borderWidth: 2,
                    borderColor: '#ffffff',
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '72%',
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(ctx) {
                                return ' ' + ctx.label + ': Rp ' + Number(ctx.raw || 0).toLocaleString('id-ID');
                            }
                        }
                    }
                }
            }
        });

        // 2. Bar Chart
        const barLabels = rawLabels.map(l => l.length > 15 ? l.substring(0, 15) + '...' : l);
        new Chart(document.getElementById('barChartMaster'), {
            type: 'bar',
            data: {
                labels: barLabels,
                datasets: [{
                    label: 'Nilai',
                    data: rawValues.map(v => v / 1e9),
                    backgroundColor: '#1d4ed8',
                    borderRadius: 4,
                    barPercentage: 0.6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(ctx) {
                                return ' Nilai: Rp ' + Number(ctx.raw).toFixed(2) + ' Miliar';
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        ticks: {
                            font: { size: 9 },
                            callback: function(v) { return v + ' M'; }
                        },
                        grid: { color: '#f1f5f9' }
                    },
                    y: {
                        ticks: { font: { size: 9, weight: 'bold' } },
                        grid: { display: false }
                    }
                }
            }
        });
        </script>
    <?php endif; ?>

</div>

<script>
function openPrintBoard() {
    const printUrl = new URL(window.location.href);
    printUrl.searchParams.set('print', '1');
    const printWindow = window.open(printUrl.toString(), '_blank');

    if (printWindow) {
        printWindow.focus();
    }
}

if (new URLSearchParams(window.location.search).get('print') === '1') {
    window.addEventListener('load', function() {
        window.setTimeout(function() {
            window.print();
        }, 300);
    });
}
</script>

</body>
</html>
