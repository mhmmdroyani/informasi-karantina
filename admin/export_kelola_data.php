<?php
require_once __DIR__ . '/../config/auth.php';
require_admin();

use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Keep failures useful to the admin without exposing database or server details.
$exportStage = 'memuat library Excel';

try {
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../includes/period_aggregation.php';

$exportStage = 'menghubungkan database';
require_once __DIR__ . '/../config/koneksi.php';

$jenis = $_GET['jenis'] ?? 'domestik_masuk';
if (!in_array($jenis, ['ekspor', 'impor', 'domestik_keluar', 'domestik_masuk'], true)) {
    $jenis = 'domestik_masuk';
}

$kategori = $_GET['kategori'] ?? 'hewan';
if (!in_array($kategori, ['hewan', 'ikan', 'tumbuhan'], true)) {
    $kategori = 'hewan';
}

$legacyPeriod = trim($_GET['periode'] ?? '');
$periodeA = trim($_GET['periode_a'] ?? $legacyPeriod);
$periodeB = trim($_GET['periode_b'] ?? $legacyPeriod);
$validPeriod = static function ($period) {
    return preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $period)
        && checkdate((int)substr($period, 5, 2), 1, (int)substr($period, 0, 4));
};
if (!$validPeriod($periodeA) || !$validPeriod($periodeB)) {
    http_response_code(400);
    exit('Pilih Periode A dan Periode B yang valid sebelum mengunduh data.');
}
$rangeStart = min($periodeA, $periodeB);
$rangeEnd = max($periodeA, $periodeB);

$exportStage = 'mengambil data dari database';
$stmt = $pdo->prepare(
    'SELECT *
     FROM ekspor_komoditas
     WHERE jenis_kegiatan = ? AND kategori = ?
       AND periode >= ? AND periode <= ?
       AND periode REGEXP \'^[0-9]{4}-(0[1-9]|1[0-2])$\'
     ORDER BY is_published DESC, nilai_ekspor DESC, frekuensi DESC'
);
$stmt->execute([$jenis, $kategori, $rangeStart, $rangeEnd]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
if ($rangeStart !== $rangeEnd) {
    $rows = aggregate_monthly_period_rows($rows);
}

$labelKegiatan = get_label_kegiatan($jenis);
$labelKategori = strtoupper($kategori);
$labelPeriodeA = format_label_periode_kode($rangeStart) ?: $rangeStart;
$labelPeriodeB = format_label_periode_kode($rangeEnd) ?: $rangeEnd;
$labelPeriode = $rangeStart === $rangeEnd ? $labelPeriodeA : "{$labelPeriodeA} - {$labelPeriodeB}";
$columns = [
    'No',
    'Klasifikasi',
    'Komoditas',
    'Frekuensi',
    'Volume',
    'Satuan',
    'Harga Barang (Rp)',
    'Daerah Asal',
    'Daerah Tujuan',
    'Negara Tujuan',
    'Status',
];
$lastColumn = 'K';
$wrappedColumnWidths = [2 => 32, 7 => 26, 8 => 26, 9 => 26];

$exportStage = 'membuat worksheet Excel';
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Rekap Data');
$exportStage = 'menulis judul dan header Excel';
$sheet->mergeCells("A1:{$lastColumn}1");
$sheet->mergeCells("A2:{$lastColumn}2");
$sheet->mergeCells("A3:{$lastColumn}3");
$sheet->setCellValue('A1', "REKAP DATA {$labelKegiatan} KARANTINA {$labelKategori}");
$sheet->setCellValue('A2', 'Balai Karantina Hewan, Ikan, dan Tumbuhan Kalimantan Selatan');
$sheet->setCellValue('A3', "Periode: {$labelPeriode}");

foreach ($columns as $index => $column) {
    $coordinate = Coordinate::stringFromColumnIndex($index + 1) . '5';
    $sheet->setCellValue($coordinate, $column);
}

foreach ($rows as $index => $row) {
    $exportStage = 'memasukkan baris data ke Excel (' . ($index + 1) . ')';
    $excelRow = $index + 6;
    $wrappedLines = 1;
    $commodity = (string)($row['nama_komoditas'] ?? '');
    if (!empty($row['nama_komoditas_lokal'])) {
        $commodity .= "\n" . $row['nama_komoditas_lokal'];
    }
    $values = [
        $index + 1,
        (string)($row['klasifikasi'] ?? '-'),
        $commodity,
        (int)($row['frekuensi'] ?? 0),
        (float)($row['volume'] ?? 0),
        (string)($row['satuan'] ?? '-'),
        (float)($row['nilai_ekspor'] ?? 0),
        (string)($row['daerah_asal'] ?? '-'),
        (string)($row['daerah_tujuan'] ?? '-'),
        (string)($row['negara_tujuan'] ?? '-'),
        (string)($row['publication_status'] ?? ((int)($row['is_published'] ?? 0) === 1 ? 'Published' : 'Draft')),
    ];

    foreach ($values as $columnIndex => $value) {
        $coordinate = Coordinate::stringFromColumnIndex($columnIndex + 1) . $excelRow;
        if (is_int($value) || is_float($value)) {
            $sheet->setCellValue($coordinate, $value);
        } else {
            $sheet->setCellValueExplicit($coordinate, $value, DataType::TYPE_STRING);
        }

        if (isset($wrappedColumnWidths[$columnIndex])) {
            $cellLines = 0;
            foreach (explode("\n", (string)$value) as $line) {
                $cellLines += max(1, (int)ceil(mb_strlen($line, 'UTF-8') / $wrappedColumnWidths[$columnIndex]));
            }
            $wrappedLines = max($wrappedLines, $cellLines);
        }
    }
    $sheet->getRowDimension($excelRow)->setRowHeight(max(30, $wrappedLines * 15 + 18));
}

$lastRow = max(5, count($rows) + 5);
$exportStage = 'memformat tabel Excel';
$sheet->getStyle("A1:{$lastColumn}3")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->getStyle("A1:{$lastColumn}3")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16)->getColor()->setRGB('17365D');
$sheet->getStyle('A2')->getFont()->setBold(true)->setSize(11);
$sheet->getStyle('A3')->getFont()->setItalic(true)->setSize(10)->getColor()->setRGB('5B6573');
$sheet->getStyle("A5:{$lastColumn}5")->applyFromArray([
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '17365D']],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
        'vertical' => Alignment::VERTICAL_CENTER,
        'wrapText' => true,
    ],
]);
$sheet->getStyle("A5:{$lastColumn}{$lastRow}")->applyFromArray([
    'borders' => [
        'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'D7DEE8']],
    ],
]);
if ($rows !== []) {
    $sheet->getStyle("A6:{$lastColumn}{$lastRow}")->getFont()->setSize(10);
    $sheet->getStyle("A6:{$lastColumn}{$lastRow}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
    $sheet->getStyle("A6:A{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle("D6:E{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
    $sheet->getStyle("G6:G{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
    $sheet->getStyle("C6:C{$lastRow}")->getAlignment()->setWrapText(true);
    $sheet->getStyle("H6:K{$lastRow}")->getAlignment()->setWrapText(true);
    $sheet->getStyle("K6:K{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle("D6:D{$lastRow}")->getNumberFormat()->setFormatCode('#,##0');
    $sheet->getStyle("E6:E{$lastRow}")->getNumberFormat()->setFormatCode('#,##0.00');
    $sheet->getStyle("G6:G{$lastRow}")->getNumberFormat()->setFormatCode('#,##0');
    for ($row = 6; $row <= $lastRow; $row++) {
        if (($row - 6) % 2 === 1) {
            $sheet->getStyle("A{$row}:{$lastColumn}{$row}")
                ->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()
                ->setRGB('F3F6FA');
        }
    }
}
$sheet->getRowDimension(5)->setRowHeight(32);
$sheet->getRowDimension(1)->setRowHeight(28);
$sheet->getRowDimension(2)->setRowHeight(22);
$sheet->getRowDimension(3)->setRowHeight(20);

foreach (['A' => 7, 'B' => 20, 'C' => 32, 'D' => 14, 'E' => 16, 'F' => 16, 'G' => 22, 'H' => 26, 'I' => 26, 'J' => 26, 'K' => 14] as $column => $width) {
    $sheet->getColumnDimension($column)->setWidth($width);
}
$sheet->getSheetView()->setZoomScale(75);

$exportStage = 'mengatur orientasi halaman Excel';
$sheet->getPageSetup()
    ->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE)
    ->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4)
    ->setFitToWidth(1)
    ->setFitToHeight(0);
$exportStage = 'mengatur area cetak Excel';
$sheet->getPageSetup()->setPrintArea("A1:{$lastColumn}{$lastRow}");
$exportStage = 'mengatur margin cetak Excel';
$sheet->getPageMargins()
    ->setTop(0.5)
    ->setRight(0.5)
    ->setBottom(0.5)
    ->setLeft(0.5);

$filename = sprintf(
    'Rekap_%s_%s_%s.xlsx',
    preg_replace('/[^A-Za-z0-9_-]/', '_', $jenis),
    preg_replace('/[^A-Za-z0-9_-]/', '_', $kategori),
    $rangeStart === $rangeEnd ? $rangeStart : $rangeStart . '_sd_' . $rangeEnd
);

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$exportStage = 'membuat file XLSX';
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
} catch (Throwable $error) {
    $errorId = bin2hex(random_bytes(4));
    error_log(sprintf(
        '[Excel export %s] Stage: %s; %s: %s',
        $errorId,
        $exportStage,
        get_class($error),
        $error->getMessage()
    ));

    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/plain; charset=UTF-8');
    }

    echo "Unduhan Excel gagal pada tahap: {$exportStage}.\n";
    echo "Kode diagnostik: {$errorId}.";
    exit;
}
