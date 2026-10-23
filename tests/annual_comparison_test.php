<?php

require_once __DIR__ . '/../includes/annual_comparison.php';

function expect_same($expected, $actual, string $message): void
{
    if ($expected !== $actual) {
        throw new RuntimeException(
            $message . "\nExpected: " . var_export($expected, true)
            . "\nActual: " . var_export($actual, true)
        );
    }
}

expect_same(
    ['2023', '2024'],
    published_comparison_years(['2024-01', '2023-12', '2024-01', '2024-13', 'bad', null]),
    'Only distinct valid published years should be listed in chronological order.'
);

$monthly = aggregate_comparison_months(
    ['2022', '2023', '2024'],
    [
        [
            'periode' => '2024-01',
            'nilai_ekspor' => 100,
            'volume' => 2,
            'satuan' => 'ton',
            'frekuensi' => 3,
            'lokasi_tujuan' => 'Banjar, Tanah Laut'
        ],
        [
            'periode' => '2024-01',
            'nilai_ekspor' => 50,
            'volume' => 500,
            'satuan' => 'kg',
            'frekuensi' => 2,
            'lokasi_tujuan' => 'BANJAR; Banjarmasin'
        ],
        [
            'periode' => '2023-12',
            'nilai_ekspor' => 25,
            'volume' => 1,
            'satuan' => 'ton',
            'frekuensi' => 1,
            'lokasi_tujuan' => 'Jakarta'
        ],
        [
            'periode' => '2024-13',
            'nilai_ekspor' => 999,
            'volume' => 999,
            'satuan' => 'kg',
            'frekuensi' => 999,
            'lokasi_tujuan' => 'Invalid'
        ]
    ]
);

expect_same(range(1, 12), array_keys($monthly['2024']['nilai']), 'Each year should have all 12 months.');
expect_same(150.0, $monthly['2024']['nilai'][1], 'Monthly value should aggregate rows.');
expect_same(2500.0, $monthly['2024']['volume'][1], 'Compatible volumes should be converted to kilograms.');
expect_same(5, $monthly['2024']['frekuensi'][1], 'Monthly frequency should aggregate rows.');
expect_same(3, $monthly['2024']['negara'][1], 'Locations should be counted uniquely per month.');
expect_same(0.0, $monthly['2024']['nilai'][2], 'Months without data should be zero-filled.');
expect_same(0, $monthly['2024']['negara'][2], 'Months without destinations should have zero locations.');
expect_same(25.0, $monthly['2023']['nilai'][12], 'Rows should aggregate into the matching year and month.');
expect_same(0.0, $monthly['2023']['nilai'][1], 'Other months should remain zero-filled.');
expect_same(0.0, $monthly['2022']['nilai'][1], 'A year without rows should still be available as a zero-filled series.');

echo "Annual comparison tests passed.\n";
