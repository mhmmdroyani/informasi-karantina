<?php
// admin/template_sample.php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/auth.php';

require_admin();

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

// ======================================================
// 1. AMBIL KATEGORI
// ======================================================

$kategori = strtolower(trim($_GET['kategori'] ?? 'hewan'));

// Kategori yang diperbolehkan
$kategoriValid = ['hewan', 'ikan', 'tumbuhan'];

if (!in_array($kategori, $kategoriValid, true)) {
    $kategori = 'hewan';
}

// ======================================================
// 2. TENTUKAN JUDUL DAN NAMA FILE
// ======================================================

switch ($kategori) {

    case 'ikan':
        $judul = 'REKAP EKSPOR KARANTINA IKAN TA 2026';
        $filename = 'Template_Ekspor_Karantina_Ikan_2026.xlsx';
        break;

    case 'tumbuhan':
        $judul = 'REKAP EKSPOR KARANTINA TUMBUHAN TA 2026';
        $filename = 'Template_Ekspor_Karantina_Tumbuhan_2026.xlsx';
        break;

    case 'hewan':
    default:
        $judul = 'REKAP EKSPOR KARANTINA HEWAN TA 2026';
        $filename = 'Template_Ekspor_Karantina_Hewan_2026.xlsx';
        break;
}

// ======================================================
// 3. BUAT SPREADSHEET
// ======================================================

$spreadsheet = new Spreadsheet();

$sheet = $spreadsheet->getActiveSheet();

// Nama sheet mengikuti file sample
$sheet->setTitle('REKAP BALAI EKSPOR FINAL');

// ======================================================
// 4. JUDUL
// ======================================================

// Baris 1
$sheet->setCellValue('A1', $judul);
$sheet->mergeCells('A1:G1');

// Baris 2
$sheet->setCellValue(
    'A2',
    'Balai Karantina Hewan, Ikan, dan Tumbuhan Kalimantan Selatan'
);
$sheet->mergeCells('A2:G2');

// Baris 3 dikosongkan sesuai sample


// ======================================================
// 5. HEADER TABEL
// ======================================================

// Header utama
$sheet->setCellValue('A4', 'No');
$sheet->setCellValue('B4', 'Komoditas');

$sheet->setCellValue('C4', '20.. (Jan - ...)');

$sheet->setCellValue('G4', 'NEGARA TUJUAN');

// Merge sesuai file sample
$sheet->mergeCells('A4:A5');
$sheet->mergeCells('B4:B5');
$sheet->mergeCells('C4:F4');
$sheet->mergeCells('G4:G5');

// Sub-header
$sheet->setCellValue('C5', 'Frekuensi');
$sheet->setCellValue('D5', 'Volume');
$sheet->setCellValue('E5', 'Satuan');
$sheet->setCellValue('F5', 'Nilai Barang (Rp)');


// ======================================================
// 6. BARIS DATA KOSONG
// ======================================================

// Sample memiliki 12 baris data
for ($i = 1; $i <= 12; $i++) {

    $row = $i + 5;

    // Nomor urut
    $sheet->setCellValue("A{$row}", $i);

    // Kolom lainnya sengaja dikosongkan
    $sheet->setCellValue("B{$row}", '');
    $sheet->setCellValue("C{$row}", '');
    $sheet->setCellValue("D{$row}", '');
    $sheet->setCellValue("E{$row}", '');
    $sheet->setCellValue("F{$row}", '');
    $sheet->setCellValue("G{$row}", '');
}


// ======================================================
// 7. TANGGAL
// ======================================================

// Mengikuti format file sample
$sheet->setCellValue(
    'D20',
    'Banjarmasin, 15 September 2026'
);

// Merge seperti file sample
$sheet->mergeCells('D20:F20');


// ======================================================
// 8. STYLE JUDUL
// ======================================================

$sheet->getStyle('A1:G1')->getFont()
    ->setBold(true)
    ->setName('Calibri')
    ->setSize(11);

$sheet->getStyle('A1:G1')->getAlignment()
    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
    ->setVertical(Alignment::VERTICAL_CENTER);


$sheet->getStyle('A2:G2')->getFont()
    ->setBold(true)
    ->setName('Calibri')
    ->setSize(11);

$sheet->getStyle('A2:G2')->getAlignment()
    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
    ->setVertical(Alignment::VERTICAL_CENTER);


// ======================================================
// 9. STYLE HEADER
// ======================================================

$headerRange = 'A4:G5';

$sheet->getStyle($headerRange)->getFont()
    ->setBold(true)
    ->setName('Calibri')
    ->setSize(11);

$sheet->getStyle($headerRange)->getFill()
    ->setFillType(Fill::FILL_SOLID)
    ->getStartColor()
    ->setRGB('D0CECE');

$sheet->getStyle($headerRange)->getAlignment()
    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
    ->setVertical(Alignment::VERTICAL_CENTER)
    ->setWrapText(true);


// ======================================================
// 10. BORDER HEADER
// ======================================================

$sheet->getStyle($headerRange)->getBorders()->getAllBorders()
    ->setBorderStyle(Border::BORDER_THIN);


// ======================================================
// 11. STYLE BARIS DATA
// ======================================================

$dataRange = 'A6:G17';

$sheet->getStyle($dataRange)->getBorders()->getAllBorders()
    ->setBorderStyle(Border::BORDER_THIN);

$sheet->getStyle('A6:A17')
    ->getAlignment()
    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
    ->setVertical(Alignment::VERTICAL_TOP);

$sheet->getStyle('B6:G17')
    ->getAlignment()
    ->setVertical(Alignment::VERTICAL_TOP);


// ======================================================
// 12. FORMAT ANGKA
// ======================================================

// Frekuensi
$sheet->getStyle('C6:C17')
    ->getNumberFormat()
    ->setFormatCode('#,##0');

// Volume
$sheet->getStyle('D6:D17')
    ->getNumberFormat()
    ->setFormatCode('#,##0.00');

// Nilai barang
$sheet->getStyle('F6:F17')
    ->getNumberFormat()
    ->setFormatCode('#,##0');


// ======================================================
// 13. FONT DATA
// ======================================================

$sheet->getStyle('A6:G17')->getFont()
    ->setName('Arial')
    ->setSize(10);


// ======================================================
// 14. STYLE TANGGAL
// ======================================================

$sheet->getStyle('D20:F20')->getFont()
    ->setName('Calibri')
    ->setSize(11);

$sheet->getStyle('D20:F20')->getAlignment()
    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
    ->setVertical(Alignment::VERTICAL_CENTER);


// ======================================================
// 15. UKURAN KOLOM
// ======================================================

$sheet->getColumnDimension('A')->setWidth(5);
$sheet->getColumnDimension('B')->setWidth(28);
$sheet->getColumnDimension('C')->setWidth(12);
$sheet->getColumnDimension('D')->setWidth(15);
$sheet->getColumnDimension('E')->setWidth(12);
$sheet->getColumnDimension('F')->setWidth(18);
$sheet->getColumnDimension('G')->setWidth(28);


// ======================================================
// 16. TINGGI BARIS
// ======================================================

$sheet->getRowDimension(1)->setRowHeight(14.4);
$sheet->getRowDimension(2)->setRowHeight(14.4);

$sheet->getRowDimension(4)->setRowHeight(14.4);
$sheet->getRowDimension(5)->setRowHeight(14.4);

for ($row = 6; $row <= 17; $row++) {
    $sheet->getRowDimension($row)->setRowHeight(14.4);
}


// ======================================================
// 17. PRINT AREA
// ======================================================

$sheet->getPageSetup()->setPrintArea('A1:G20');


// ======================================================
// 18. PAGE SETUP
// ======================================================

$sheet->getPageSetup()
    ->setOrientation(
        \PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE
    );

$sheet->getPageSetup()
    ->setPaperSize(
        \PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4
    );

$sheet->getPageSetup()
    ->setFitToWidth(1)
    ->setFitToHeight(0);

$sheet->getPageMargins()
    ->setTop(0.5)
    ->setRight(0.5)
    ->setBottom(0.5)
    ->setLeft(0.5);


// ======================================================
// 19. DOWNLOAD FILE
// ======================================================

header(
    'Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
);

header(
    'Content-Disposition: attachment; filename="' . $filename . '"'
);

header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);

$writer->save('php://output');

exit;