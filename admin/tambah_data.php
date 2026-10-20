<?php
// admin/tambah_data.php - Input data komoditas secara manual (tanpa upload Excel)
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../config/auth.php';
require_admin();

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

    $kategori = $_POST['kategori'] ?? 'hewan';
    if (!in_array($kategori, ['hewan', 'ikan', 'tumbuhan'])) {
        $kategori = 'hewan';
    }

    $namaKomoditas = trim($_POST['nama_komoditas'] ?? '');
    $namaKomoditasLokal = trim($_POST['nama_komoditas_lokal'] ?? '');
    $frekuensi = (int)clean_number($_POST['frekuensi'] ?? 0);
    $volume = clean_number($_POST['volume'] ?? 0);
    $satuan = trim($_POST['satuan'] ?? '') ?: 'Kilogram';
    $nilaiEkspor = clean_number($_POST['nilai_ekspor'] ?? 0);
    $negaraTujuan = trim($_POST['negara_tujuan'] ?? '');

    $cal = parse_calendar_filter('bulan', $_POST);
    $periode = $cal['bln_awal'] . '_' . $cal['bln_akhir'];
    $tanggalMulai = $cal['tgl_awal'];
    $tanggalSelesai = $cal['tgl_akhir'];
    $langsungPublish = isset($_POST['langsung_publish']) ? 1 : 0;

    if (empty($namaKomoditas)) {
        $error = 'Nama komoditas wajib diisi!';
    } else {
        $stmt = $pdo->prepare("INSERT INTO ekspor_komoditas
            (jenis_kegiatan, kategori, nama_komoditas, nama_komoditas_lokal, frekuensi, volume, satuan, nilai_ekspor, negara_tujuan, periode, tanggal_mulai, tanggal_selesai, is_published)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $jenis,
            $kategori,
            $namaKomoditas,
            $namaKomoditasLokal ?: null,
            $frekuensi,
            $volume,
            $satuan,
            $nilaiEkspor,
            $negaraTujuan,
            $periode,
            $tanggalMulai,
            $tanggalSelesai,
            $langsungPublish
        ]);

        $message = 'Data komoditas "' . htmlspecialchars($namaKomoditas) . '" berhasil ditambahkan.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Data Komoditas - Admin Karantina</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="admin.css?v=<?= @filemtime(__DIR__ . '/admin.css') ?: time() ?>">
</head>
<body>
    <?php require 'navbar.php'; ?>

    <div class="admin-main" style="max-width: 860px;">
        <div class="page-header">
            <div>
                <span class="eyebrow"><span class="dot dot-brand"></span> Input Manual</span>
                <h1 class="page-title">Tambah Data Komoditas</h1>
                <p class="page-desc">Masukkan satu data komoditas secara manual tanpa perlu mengunggah file Excel. Data langsung tersimpan ke database dan otomatis tersedia di dashboard.</p>
            </div>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert alert-success" style="margin-bottom: 16px; justify-content: space-between; flex-wrap: wrap;">
                <div style="display: flex; align-items: flex-start; gap: 10px;">
                    <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <div><?= $message ?></div>
                </div>
                <a href="kelola_data.php?jenis=<?= $currentJenis ?>" class="btn btn-primary btn-sm">Lihat di Kelola Data &rarr;</a>
            </div>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger" style="margin-bottom: 16px;">
                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <div><?= htmlspecialchars($error) ?></div>
            </div>
        <?php endif; ?>

        <div class="card card-pad">
            <form action="" method="POST" data-confirm="Simpan data komoditas ini ke database?" style="display: flex; flex-direction: column; gap: 22px;">
                <?= csrf_field() ?>

                <div>
                    <label class="field-label">Pilih Jenis Lalu Lintas Karantina <span style="color: var(--danger);">*</span></label>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 10px;">
                        <label class="radio-card">
                            <input type="radio" name="jenis_kegiatan" value="domestik_masuk" <?= $currentJenis === 'domestik_masuk' ? 'checked' : '' ?>>
                            <div><div class="title">Domestik Masuk</div><div class="sub">Masuk Kalsel (Antar-Area)</div></div>
                        </label>
                        <label class="radio-card">
                            <input type="radio" name="jenis_kegiatan" value="domestik_keluar" <?= $currentJenis === 'domestik_keluar' ? 'checked' : '' ?>>
                            <div><div class="title">Domestik Keluar</div><div class="sub">Keluar Kalsel (Antar-Area)</div></div>
                        </label>
                        <label class="radio-card">
                            <input type="radio" name="jenis_kegiatan" value="ekspor" <?= $currentJenis === 'ekspor' ? 'checked' : '' ?>>
                            <div><div class="title">Ekspor</div><div class="sub">Ke Luar Negeri</div></div>
                        </label>
                        <label class="radio-card">
                            <input type="radio" name="jenis_kegiatan" value="impor" <?= $currentJenis === 'impor' ? 'checked' : '' ?>>
                            <div><div class="title">Impor</div><div class="sub">Dari Luar Negeri</div></div>
                        </label>
                    </div>
                </div>

                <div>
                    <label class="field-label">Kategori Komoditas <span style="color: var(--danger);">*</span></label>
                    <select name="kategori" id="selectKategori" class="field-input" onchange="toggleLokalField()">
                        <option value="hewan">Hewan (KH)</option>
                        <option value="ikan">Ikan (KI)</option>
                        <option value="tumbuhan">Tumbuhan (KT)</option>
                    </select>
                </div>

                <div>
                    <label class="field-label">Nama Komoditas <span style="color: var(--danger);">*</span></label>
                    <input type="text" name="nama_komoditas" required class="field-input" placeholder="Contoh: Sarang Burung Walet">
                </div>

                <div id="fieldLokal" style="display: none;">
                    <label class="field-label">Nama Komoditas (Lokal / Indonesia)</label>
                    <input type="text" name="nama_komoditas_lokal" class="field-input" placeholder="Khusus kategori Ikan">
                </div>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 16px;">
                    <div>
                        <label class="field-label">Frekuensi</label>
                        <input type="text" name="frekuensi" value="0" class="field-input">
                    </div>
                    <div>
                        <label class="field-label">Volume</label>
                        <input type="text" name="volume" value="0" class="field-input">
                    </div>
                    <div>
                        <label class="field-label">Satuan</label>
                        <select name="satuan" class="field-input">
                            <option value="ton">Ton</option>
                            <option value="kilogram">Kilogram</option>
                            <option value="ekor">Ekor</option>
                            <option value="meterkubik">Meter Kubik</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="field-label">Nilai Komoditas (Rp)</label>
                    <input type="text" name="nilai_ekspor" value="0" class="field-input">
                </div>

                <div>
                    <label class="field-label">Negara / Daerah Tujuan (atau Perusahaan)</label>
                    <input type="text" name="negara_tujuan" class="field-input" placeholder="Pisahkan dengan koma jika lebih dari satu">
                </div>

                <!-- Kalender Periode -->
                <div class="card card-pad" style="background: var(--bg);">
                    <label class="field-label" style="display: block; margin-bottom: 12px;">Periode Data (Bulan) <span style="color: var(--danger);">*</span></label>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 12px;">
                        <div>
                            <label class="field-label">Dari Bulan</label>
                            <input type="month" name="bln_awal" value="<?= date('Y-m') ?>" class="field-input">
                        </div>
                        <div>
                            <label class="field-label">Sampai Bulan</label>
                            <input type="month" name="bln_akhir" value="<?= date('Y-m') ?>" class="field-input">
                        </div>
                    </div>
                </div>

                <label style="display: flex; align-items: center; gap: 8px; font-size: 13px; cursor: pointer;">
                    <input type="checkbox" name="langsung_publish" value="1">
                    <span>Langsung publikasikan ke board publik setelah disimpan</span>
                </label>

                <div style="display: flex; align-items: center; justify-content: space-between; padding-top: 12px; border-top: 1px solid var(--line);">
                    <a href="kelola_data.php" style="font-size: 13px; color: var(--ink-muted); text-decoration: none;">Batal / Ke Kelola Data</a>
                    <button type="submit" class="btn btn-primary">
                        <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                        <span>Simpan Data</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function toggleLokalField() {
        const kategori = document.getElementById('selectKategori').value;
        document.getElementById('fieldLokal').style.display = kategori === 'ikan' ? 'block' : 'none';
    }
    toggleLokalField();
    </script>
</body>
</html>
