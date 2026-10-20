<?php

require_once __DIR__ . '/../includes/volume_metrics.php';

function assert_same_value($expected, $actual, $message) {
    if ($expected !== $actual) {
        fwrite(STDERR, $message . ': expected ' . var_export($expected, true) . ', got ' . var_export($actual, true) . PHP_EOL);
        exit(1);
    }
}

function assert_near_value($expected, $actual, $message) {
    if (abs($expected - $actual) > 0.0000001) {
        fwrite(STDERR, $message . ': expected ' . $expected . ', got ' . $actual . PHP_EOL);
        exit(1);
    }
}

$belowThreshold = aggregate_volume_rows([
    ['volume' => 999.99, 'satuan' => 'kg']
]);
assert_near_value(999.99, $belowThreshold['mass_kg']['value'], 'Below conversion boundary');
assert_same_value('999,99 Kilogram', format_volume_groups($belowThreshold), 'Below-boundary display');

$atThreshold = aggregate_volume_rows([
    ['volume' => 1000, 'satuan' => 'Kilogram']
]);
assert_same_value('1 Ton', format_volume_groups($atThreshold), 'At-boundary display');

$aboveThreshold = aggregate_volume_rows([
    ['volume' => 1000.01, 'satuan' => 'Kilograms']
]);
assert_near_value(1000.01, $aboveThreshold['mass_kg']['value'], 'Above conversion boundary');
assert_same_value('1,00001 Ton', format_volume_groups($aboveThreshold), 'Above-boundary precision');

$boundaryRows = [
    ['volume' => 600, 'satuan' => 'Kilogram'],
    ['volume' => 400, 'satuan' => 'kg']
];
$originalBoundaryRows = $boundaryRows;
$aggregatedBoundary = aggregate_volume_rows($boundaryRows);
assert_same_value($originalBoundaryRows, $boundaryRows, 'Source volume values remain unchanged');
assert_same_value('1 Ton', format_volume_groups($aggregatedBoundary), 'Aggregate crosses conversion boundary once');

$mixedMassUnits = aggregate_volume_rows([
    ['volume' => 500, 'satuan' => 'kg'],
    ['volume' => 0.5, 'satuan' => 'Ton'],
    ['volume' => 250, 'satuan' => 'Kilograms']
]);
assert_near_value(1250, $mixedMassUnits['mass_kg']['value'], 'Equivalent mass aggregation');
assert_same_value('1,25 Ton', format_volume_groups($mixedMassUnits), 'Mass converted exactly once for display');

$mixedUnrelatedUnits = aggregate_volume_rows([
    ['volume' => 100, 'satuan' => 'kg'],
    ['volume' => 7, 'satuan' => 'Ekor'],
    ['volume' => 5, 'satuan' => 'Box']
]);
assert_same_value(3, count($mixedUnrelatedUnits), 'Different units remain separate');
assert_same_value('100 Kilogram · 7 Ekor · 5 Box', format_volume_groups($mixedUnrelatedUnits), 'Separate-unit display');

$periodA = aggregate_volume_rows([
    ['volume' => 1000, 'satuan' => 'kg'],
    ['volume' => 4, 'satuan' => 'Ekor']
]);
$periodB = aggregate_volume_rows([
    ['volume' => 1.5, 'satuan' => 'Ton'],
    ['volume' => 8, 'satuan' => 'Ekor'],
    ['volume' => 3, 'satuan' => 'Box']
]);
$comparisonDatasets = build_volume_comparison_datasets($periodA, $periodB);
assert_same_value(3, count($comparisonDatasets), 'Comparison keeps independent unit series');
assert_near_value(1000, $comparisonDatasets[0]['data'][0], 'Comparison mass base-unit value');
assert_near_value(1500, $comparisonDatasets[0]['data'][1], 'Comparison ton-to-kilogram conversion');
assert_near_value(0, $comparisonDatasets[2]['data'][0], 'Missing comparison unit remains zero in its own series');

$change = compare_volume_groups($periodA, $periodB);
assert_same_value('is-up', $change['direction'], 'Mixed per-unit comparison direction');
assert_same_value('Kilogram: +50.0% · Ekor: +100.0% · Box: Baru', $change['label'], 'Per-unit percentage changes');

echo "Volume metric boundary and mixed-unit tests passed." . PHP_EOL;
