<?php
// admin/template_sample.php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/auth.php';
require_admin();

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

$kategori = $_GET['kategori'] ?? 'hewan';
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Ops');

if ($kategori === 'ikan') {
    $filename = 'Template_Ekspor_Ikan_KI.xlsx';
    
    // Baris 1: Judul
    $sheet->setCellValue('A1', 'DATA REKAPITULASI EKSPOR KOMODITAS KARANTINA IKAN');
    $sheet->mergeCells('A1:H1');
    
    // Baris 2: Header
    $headers = ['No', 'Media Pembawa (EN)', 'Media Pembawa (ID)', 'Frekuensi', 'Volume', 'Satuan', 'Nilai Komoditi', 'Negara Tujuan'];
    $sheet->fromArray($headers, null, 'A2');
    
    // Contoh Baris 3+
    $data = [
        [1, 'Black Tiger Shrimp', 'Udang Windu', 124, 250000.50, 'Kilogram', 35000000000, 'Jepang, Amerika Serikat, China'],
        [2, 'Yellowfin Tuna', 'Ikan Tuna Sirip Kuning', 88, 140200.00, 'Kilogram', 22500000000, 'Jepang, Korea Selatan'],
        [3, 'Blue Swimming Crab', 'Rajungan', 65, 85000.00, 'Kilogram', 18200000000, 'Amerika Serikat, Singapura'],
        [4, 'Grouper', 'Ikan Kerapu Segar', 42, 45100.00, 'Kilogram', 9800000000, 'Hong Kong, Malaysia'],
        [5, 'Seaweed Eucheuma', 'Rumput Laut Kering', 110, 480000.00, 'Kilogram', 14500000000, 'China, Vietnam'],
        [6, 'Vannamei Shrimp', 'Udang Vaname', 95, 210000.00, 'Kilogram', 28000000000, 'Amerika Serikat, Uni Eropa'],
    ];
    $sheet->fromArray($data, null, 'A3');

} elseif ($kategori === 'tumbuhan') {
    $filename = 'Template_Ekspor_Tumbuhan_KT.xlsx';
    
    // Baris 1: Judul
    $sheet->setCellValue('A1', 'DATA REKAPITULASI EKSPOR KOMODITAS KARANTINA TUMBUHAN');
    $sheet->mergeCells('A1:G1');
    
    // Baris 2: Header
    $headers = ['No', 'Komoditas', 'Frekuensi', 'Volume', 'Satuan', 'Nilai Komoditi', 'Negara Tujuan'];
    $sheet->fromArray($headers, null, 'A2');
    
    // Contoh baris data satu periode
    $data = [
        [1, 'Kelapa Bulat', 380, 1850000.00, 'Kilogram', 21000000000, 'China, Thailand, Malaysia'],
        [2, 'Kopi Arabika Toraja', 215, 510000.00, 'Kilogram', 49000000000, 'Amerika Serikat, Jepang, Jerman'],
        [3, 'Cengkeh', 110, 160000.00, 'Kilogram', 19500000000, 'India, Uni Emirat Arab'],
        [4, 'Pala Biji', 95, 115000.00, 'Kilogram', 16000000000, 'Belanda, Vietnam, Jepang'],
        [5, 'Biji Kakao Fermentasi', 165, 790000.00, 'Kilogram', 42000000000, 'Malaysia, Singapura, Belgia'],
        [6, 'Kayu Manis', 75, 980000.00, 'Kilogram', 9800000000, 'Amerika Serikat, Belanda'],
    ];
    $sheet->fromArray($data, null, 'A3');

} else {
    // Hewan (default)
    $filename = 'Template_Ekspor_Hewan_KH.xlsx';
    
    // Baris 1: Judul
    $sheet->setCellValue('A1', 'DATA REKAPITULASI EKSPOR KOMODITAS KARANTINA HEWAN');
    $sheet->mergeCells('A1:G1');
    
    // Baris 2: Header
    $headers = ['No', 'Media Pembawa', 'Frekuensi', 'Volume', 'Satuan', 'Nilai Komoditi', 'Negara Tujuan'];
    $sheet->fromArray($headers, null, 'A2');
    
    // Contoh Baris 3+
    $data = [
        [1, 'Sarang Burung Walet', 342, 12500.80, 'Kilogram', 185000000000, 'China, Hong Kong, Taiwan, Singapura'],
        [2, 'Daging Ayam Olahan', 120, 850000.00, 'Kilogram', 45000000000, 'Singapura, Jepang, Timor Leste'],
        [3, 'DOC Ayam Pedaging', 45, 150000.00, 'Ekor', 1850000000, 'Timor Leste, Papua Nugini'],
        [4, 'Bulu Bebek Bersih', 85, 95000.00, 'Kilogram', 12400000000, 'China, Vietnam'],
        [5, 'Telur Asin Matang', 62, 45000.00, 'Butir', 450000000, 'Singapura, Brunei Darussalam'],
        [6, 'Sapi Potong Hidup', 18, 2500.00, 'Ekor', 62500000000, 'Malaysia, Brunei Darussalam'],
    ];
    $sheet->fromArray($data, null, 'A3');
}

// Styling Header
$lastCol = $sheet->getHighestColumn();
$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13);
$sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

$sheet->getStyle("A2:{$lastCol}2")->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
$sheet->getStyle("A2:{$lastCol}2")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('1E3A8A');
$sheet->getStyle("A2:{$lastCol}2")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

// Auto width
foreach (range('A', $lastCol) as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// Download
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment; filename=\"{$filename}\"");
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
