<?php
// admin/upload_ikan.php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../config/auth.php';
require_admin();

use PhpOffice\PhpSpreadsheet\IOFactory;

$message = '';
$error = '';

$currentJenis = $_GET['jenis'] ?? 'domestik_masuk';
if (!in_array($currentJenis, ['ekspor', 'impor', 'domestik_keluar', 'domestik_masuk'])) {
    $currentJenis = 'domestik_masuk';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $jenis = $_POST['jenis_kegiatan'] ?? 'domestik_masuk';
    if (!in_array($jenis, ['ekspor', 'impor', 'domestik_keluar', 'domestik_masuk'])) {
        $jenis = 'domestik_masuk';
    }
    $currentJenis = $jenis;

        $calMode = $_POST['cal_mode'] ?? 'tahun';
    $cal = parse_calendar_filter($calMode, $_POST);
    $tanggalMulai = $cal['tgl_awal'];
    $tanggalSelesai = $cal['tgl_akhir'];

    $periode = trim($_POST['periode'] ?? '');
    if (empty($periode)) {
        if ($calMode === 'tahun') {
            $periode = (string)$cal['thn_awal'];
        } elseif ($calMode === 'bulan') {
            $periode = $cal['bln_awal'] . '_' . $cal['bln_akhir'];
        } else {
            $periode = $cal['tgl_awal'] . '_' . $cal['tgl_akhir'];
        }
    }
    $mode = $_POST['mode'] ?? 'append';
    $langsungPublish = 0;

    if (empty($periode)) {
        $error = 'Rentang / Kode Periode wajib diisi manual!';
    } elseif (!isset($_FILES['file_excel']) || $_FILES['file_excel']['error'] !== UPLOAD_ERR_OK) {
        $error = 'Silakan pilih file Excel (.xlsx / .xls) yang valid!';
    } else {
        $tmpPath = $_FILES['file_excel']['tmp_name'];
        $fileName = $_FILES['file_excel']['name'];
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if (!in_array($ext, ['xlsx', 'xls', 'csv'])) {
            $error = 'Format file tidak didukung. Harap upload file Excel (.xlsx / .xls).';
        } else {
            try {
                $spreadsheet = IOFactory::load($tmpPath);
                
                // Cari sheet 'Ops' (case-insensitive) atau fallback sheet pertama
                $sheet = null;
                foreach ($spreadsheet->getSheetNames() as $sName) {
                    if (strcasecmp($sName, 'Ops') === 0) {
                        $sheet = $spreadsheet->getSheetByName($sName);
                        break;
                    }
                }
                if (!$sheet) {
                    $sheet = $spreadsheet->getSheet(0);
                }

                $rows = $sheet->toArray();
                $totalRows = count($rows);

                if ($totalRows < 3) {
                    $error = 'File Excel tidak memiliki baris data (minimal harus ada baris judul, header, dan data di baris ke-3).';
                } else {
                    $pdo->beginTransaction();

                    if ($mode === 'replace') {
                        $stmtDel = $pdo->prepare("DELETE FROM ekspor_komoditas WHERE jenis_kegiatan = ? AND kategori = 'ikan' AND periode = ?");
                        $stmtDel->execute([$jenis, $periode]);
                    }

                    $insertStmt = $pdo->prepare("INSERT INTO ekspor_komoditas 
                        (kategori, jenis_kegiatan, nama_komoditas, nama_komoditas_lokal, frekuensi, volume, satuan, nilai_ekspor, negara_tujuan, periode, tanggal_mulai, tanggal_selesai, is_published) 
                        VALUES ('ikan', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

                    $inserted = 0;
                    for ($i = 0; $i < $totalRows; $i++) {
                        $r = $rows[$i];
                        if (is_skippable_import_row($r)) {
                            continue;
                        }

                        $namaEn = trim($r[1] ?? '');
                        $namaId = trim($r[2] ?? '');

                        // Jika nama komoditas kosong, lewati
                        if (empty($namaEn) && empty($namaId)) continue;
                        if (strcasecmp($namaEn, 'total') === 0 || strcasecmp($namaId, 'total') === 0) continue;
                        if (strcasecmp($namaEn, 'jumlah') === 0 || strcasecmp($namaId, 'jumlah') === 0) continue;

                        $namaUtama = !empty($namaEn) ? $namaEn : $namaId;
                        $frekuensi = (int)clean_number($r[3] ?? 0);
                        $volume = clean_number($r[4] ?? 0);
                        $satuan = trim($r[5] ?? 'Kilogram') ?: 'Kilogram';
                        $nilai = clean_number($r[6] ?? 0);
                        $negara = trim($r[7] ?? '');

                        $insertStmt->execute([
                            $jenis,
                            $namaUtama,
                            $namaId,
                            $frekuensi,
                            $volume,
                            $satuan,
                            $nilai,
                            $negara,
                            $periode,
                            $tanggalMulai,
                            $tanggalSelesai,
                            $langsungPublish
                        ]);
                        $inserted++;
                    }

                    $pdo->commit();

                    $labelKeg = strtoupper(get_label_kegiatan($jenis));
                    if ($inserted > 0) {
                        $message = "Berhasil mengimpor <strong>{$inserted} komoditas ikan</strong> untuk aktivitas <strong>{$labelKeg}</strong> (periode: <strong>" . htmlspecialchars($periode) . "</strong>)!";
                    } else {
                        $error = 'Tidak ada baris data yang berhasil dibaca. Pastikan kolom data dimulai dari baris ke-3.';
                    }
                }
            } catch (Exception $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $error = 'Terjadi kesalahan saat memproses file: ' . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Excel Komoditas Ikan (KI) - Admin Karantina</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="admin.css?v=<?= @filemtime(__DIR__ . '/admin.css') ?: time() ?>">
</head>
<body>
    <?php require 'navbar.php'; ?>

    <div class="admin-main" style="max-width: 860px;">

        <!-- Header -->
        <div class="page-header">
            <div>
                <span class="eyebrow" style="background: var(--cat-ikan-soft); color: var(--cat-ikan); border-color: rgba(3,105,161,0.2);"><span class="dot dot-ikan"></span> Karantina Ikan (KI)</span>
                <h1 class="page-title">Upload Excel Komoditas Ikan</h1>
            </div>
            <a href="template_sample.php?kategori=ikan" class="btn btn-secondary">
                <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                <span>Download Template Ikan</span>
            </a>
        </div>

        <!-- Alerts -->
        <?php if (!empty($message)): ?>
            <div class="alert alert-success" style="margin-bottom: 16px; justify-content: space-between; flex-wrap: wrap;">
                <div style="display: flex; align-items: flex-start; gap: 10px;">
                    <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <div>
                        <div style="font-weight: 700;">Import Berhasil!</div>
                        <div style="font-size: 12px; margin-top: 2px;"><?= $message ?></div>
                    </div>
                </div>
                <a href="kelola_data.php?jenis=<?= $currentJenis ?>&kategori=ikan" class="btn btn-primary btn-sm">Lanjut Kelola & Publish Data &rarr;</a>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger" style="margin-bottom: 16px;">
                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <div><?= htmlspecialchars($error) ?></div>
            </div>
        <?php endif; ?>

        <!-- Form Upload -->
        <div class="card card-pad">
            <form action="" method="POST" enctype="multipart/form-data" style="display: flex; flex-direction: column; gap: 22px;">
                <?= csrf_field() ?>

                <!-- Pilihan Jenis Lalu Lintas -->
                <div>
                    <label class="field-label">Pilih Jenis Lalu Lintas Karantina <span style="color: var(--danger);">*</span></label>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 10px;">
                        <label class="radio-card">
                            <input type="radio" name="jenis_kegiatan" value="domestik_masuk" onchange="window.location.href='?jenis='+this.value" <?= $currentJenis === 'domestik_masuk' ? 'checked' : '' ?>>
                            <div>
                                <div class="title">Domestik Masuk</div>
                                <div class="sub">Masuk Kalsel (Antar-Area)</div>
                            </div>
                        </label>
                        <label class="radio-card">
                            <input type="radio" name="jenis_kegiatan" value="domestik_keluar" onchange="window.location.href='?jenis='+this.value" <?= $currentJenis === 'domestik_keluar' ? 'checked' : '' ?>>
                            <div>
                                <div class="title">Domestik Keluar</div>
                                <div class="sub">Keluar Kalsel (Antar-Area)</div>
                            </div>
                        </label>
                        <label class="radio-card">
                            <input type="radio" name="jenis_kegiatan" value="ekspor" onchange="window.location.href='?jenis='+this.value" <?= $currentJenis === 'ekspor' ? 'checked' : '' ?>>
                            <div>
                                <div class="title">Ekspor</div>
                                <div class="sub">Ke Luar Negeri</div>
                            </div>
                        </label>
                        <label class="radio-card">
                            <input type="radio" name="jenis_kegiatan" value="impor" onchange="window.location.href='?jenis='+this.value" <?= $currentJenis === 'impor' ? 'checked' : '' ?>>
                            <div>
                                <div class="title">Impor</div>
                                <div class="sub">Dari Luar Negeri</div>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Kategori Komoditas -->
                <div>
                    <label class="field-label">Kategori Komoditas</label>
                    <div class="tab-row grid-3">
                        <a href="upload_hewan.php?jenis=<?= $currentJenis ?>" class="tab-link kategori-tab-link"><span class="dot dot-hewan"></span> Hewan (KH)</a>
                        <a href="upload_ikan.php?jenis=<?= $currentJenis ?>" class="tab-link active kategori-tab-link"><span class="dot dot-ikan"></span> Ikan (KI)</a>
                        <a href="upload_tumbuhan.php?jenis=<?= $currentJenis ?>" class="tab-link kategori-tab-link"><span class="dot dot-tumbuhan"></span> Tumbuhan (KT)</a>
                    </div>
                </div>

                <!-- Kalender Pemilihan Rentang Periode Data -->
                <div class="card card-pad" style="background: var(--bg);">
                    <label class="field-label" style="display: block; margin-bottom: 12px;">Tentukan Rentang Periode Data (Kalender) <span style="color: var(--danger);">*</span></label>

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 12px;">
                        <div>
                            <label class="field-label">Dari Bulan</label>
                            <input type="month" name="bln_awal" id="inputBlnAwal" value="<?= date('Y-01') ?>" onchange="syncPeriodeCode()" class="field-input">
                        </div>
                        <div>
                            <label class="field-label">Sampai Bulan</label>
                            <input type="month" name="bln_akhir" id="inputBlnAkhir" value="<?= date('Y-12') ?>" onchange="syncPeriodeCode()" class="field-input">
                        </div>
                    </div>

                    <input type="hidden" name="periode" id="inputKodePeriode" value="<?= date('Y-01') ?>_<?= date('Y-12') ?>">
                    <input type="hidden" name="cal_mode" value="bulan">

                    <p class="field-hint">Data yang diunggah akan otomatis memiliki tanggal mulai dan selesai sesuai bulan yang dipilih.</p>
                </div>

                <script>
                function syncPeriodeCode() {
                    const kodeInput = document.getElementById('inputKodePeriode');
                    kodeInput.value = document.getElementById('inputBlnAwal').value + '_' + document.getElementById('inputBlnAkhir').value;
                }
                </script>

                <div>
                    <label class="field-label">Pilih File Excel (.xlsx / .xls) <span style="color: var(--danger);">*</span></label>
                    <input type="file" name="file_excel" required accept=".xlsx, .xls, .csv" class="file-input">
                </div>

                <div style="padding-top: 12px; border-top: 1px solid var(--line); display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 18px;">
                    <div>
                        <label class="field-label">Mode Import Data</label>
                        <div style="display: flex; flex-direction: column; gap: 8px;">
                            <label style="display: flex; align-items: center; gap: 8px; font-size: 13px; cursor: pointer;">
                                <input type="radio" name="mode" value="append" checked>
                                <span>Tambahkan ke data yang ada (Append)</span>
                            </label>
                            <label style="display: flex; align-items: center; gap: 8px; font-size: 13px; cursor: pointer;">
                                <input type="radio" name="mode" value="replace">
                                <span>Gantikan data lama pada jenis & periode ini (Replace)</span>
                            </label>
                        </div>
                    </div>

                    <div>
                        <label class="field-label">Status Setelah Upload</label>
                        <p class="field-hint" style="margin-top: 0;">Semua data masuk sebagai draft. Periksa dan publikasikan dari halaman Kelola Data.</p>
                    </div>
                </div>

                <div style="padding-top: 4px; display: flex; align-items: center; justify-content: space-between;">
                    <a href="kelola_data.php?jenis=<?= $currentJenis ?>&kategori=ikan" style="font-size: 13px; color: var(--ink-muted); text-decoration: none;">Batal / Ke Kelola Data</a>
                    <button type="submit" class="btn btn-primary">
                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l4-4m0 0l4 4m-4-4v12"></path></svg>
                        <span>Import Excel</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
