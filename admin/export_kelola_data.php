<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../config/auth.php';

require_admin();

use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$jenis = $_GET['jenis'] ?? 'domestik_masuk';
if (!in_array($jenis, ['ekspor', 'impor', 'domestik_keluar', 'domestik_masuk'], true)) {
    $jenis = 'domestik_masuk';
}

$kategori = $_GET['kategori'] ?? 'hewan';
if (!in_array($kategori, ['hewan', 'ikan', 'tumbuhan'], true)) {
    $kategori = 'hewan';
}

$periode = trim($_GET['periode'] ?? '');
if (
    !preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $periode)
    || !checkdate((int)substr($periode, 5, 2), 1, (int)substr($periode, 0, 4))
) {
    http_response_code(400);
    exit('Pilih periode yang valid sebelum mengunduh data.');
}

$stmt = $pdo->prepare(
    'SELECT klasifikasi, nama_komoditas, nama_komoditas_lokal, frekuensi, volume, satuan,
            nilai_ekspor, daerah_asal, daerah_tujuan, negara_tujuan, is_published
     FROM ekspor_komoditas
     WHERE jenis_kegiatan = ? AND kategori = ? AND periode = ?
     ORDER BY is_published DESC, nilai_ekspor DESC, frekuensi DESC'
);
$stmt->execute([$jenis, $kategori, $periode]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$labelKegiatan = get_label_kegiatan($jenis);
$labelKategori = strtoupper($kategori);
$labelPeriode = format_label_periode_kode($periode) ?: $periode;
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

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Rekap Data');
$sheet->mergeCells("A1:{$lastColumn}1");
$sheet->mergeCells("A2:{$lastColumn}2");
$sheet->mergeCells("A3:{$lastColumn}3");
$sheet->setCellValue('A1', "REKAP DATA {$labelKegiatan} KARANTINA {$labelKategori}");
$sheet->setCellValue('A2', 'Balai Karantina Hewan, Ikan, dan Tumbuhan Kalimantan Selatan');
$sheet->setCellValue('A3', "Periode: {$labelPeriode}");

foreach ($columns as $index => $column) {
    $sheet->setCellValue([$index + 1, 5], $column);
}

foreach ($rows as $index => $row) {
    $excelRow = $index + 6;
    $commodity = (string)$row['nama_komoditas'];
    if (!empty($row['nama_komoditas_lokal'])) {
        $commodity .= "\n" . $row['nama_komoditas_lokal'];
    }
    $values = [
        $index + 1,
        (string)($row['klasifikasi'] ?? '-'),
        $commodity,
        (int)$row['frekuensi'],
        (float)$row['volume'],
        (string)($row['satuan'] ?? '-'),
        (float)$row['nilai_ekspor'],
        (string)($row['daerah_asal'] ?? '-'),
        (string)($row['daerah_tujuan'] ?? '-'),
        (string)($row['negara_tujuan'] ?? '-'),
        (int)$row['is_published'] === 1 ? 'Published' : 'Draft',
    ];

    foreach ($values as $columnIndex => $value) {
        $cell = $sheet->getCell([$columnIndex + 1, $excelRow]);
        if (is_int($value) || is_float($value)) {
            $cell->setValue($value);
        } else {
            $cell->setValueExplicit($value, DataType::TYPE_STRING);
        }
    }
}

$lastRow = max(5, count($rows) + 5);
$sheet->getStyle("A1:{$lastColumn}3")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
$sheet->getStyle('A2')->getFont()->setBold(true)->setSize(12);
$sheet->getStyle('A3')->getFont()->setItalic(true)->setSize(10);
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
        'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '808080']],
    ],
]);
if ($rows !== []) {
    $sheet->getStyle("A6:A{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle("D6:E{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
    $sheet->getStyle("G6:G{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
    $sheet->getStyle("C6:C{$lastRow}")->getAlignment()->setWrapText(true);
    $sheet->getStyle("H6:K{$lastRow}")->getAlignment()->setWrapText(true);
    $sheet->getStyle("D6:D{$lastRow}")->getNumberFormat()->setFormatCode('#,##0');
    $sheet->getStyle("E6:E{$lastRow}")->getNumberFormat()->setFormatCode('#,##0.00');
    $sheet->getStyle("G6:G{$lastRow}")->getNumberFormat()->setFormatCode('#,##0');
}
$sheet->getRowDimension(5)->setRowHeight(32);

foreach (['A' => 7, 'B' => 20, 'C' => 32, 'D' => 14, 'E' => 16, 'F' => 16, 'G' => 22, 'H' => 26, 'I' => 26, 'J' => 26, 'K' => 14] as $column => $width) {
    $sheet->getColumnDimension($column)->setWidth($width);
}

$sheet->setAutoFilter("A5:{$lastColumn}{$lastRow}");
$sheet->freezePane('A6');
$sheet->getPageSetup()
    ->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE)
    ->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4)
    ->setFitToWidth(1)
    ->setFitToHeight(0);
$sheet->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(1, 5);
$sheet->getSheetView()->setShowGridLines(false);

$filename = sprintf(
    'Rekap_%s_%s_%s.xlsx',
    preg_replace('/[^A-Za-z0-9_-]/', '_', $jenis),
    preg_replace('/[^A-Za-z0-9_-]/', '_', $kategori),
    $periode
);

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
