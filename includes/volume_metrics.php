<?php

function normalize_volume_unit($unit) {
    $label = trim((string)$unit);
    if ($label === '') {
        $label = 'Kilogram';
    }

    $normalized = strtolower(preg_replace('/\s+/', ' ', $label));
    if (in_array($normalized, ['kg', 'kgs', 'kilogram', 'kilograms'], true)) {
        return ['key' => 'mass_kg', 'label' => 'Kilogram', 'factor' => 1.0];
    }
    if (in_array($normalized, ['t', 'ton', 'tons', 'tonne', 'tonnes', 'metric ton', 'metric tons'], true)) {
        return ['key' => 'mass_kg', 'label' => 'Kilogram', 'factor' => 1000.0];
    }

    return [
        'key' => 'unit:' . $normalized,
        'label' => $label,
        'factor' => 1.0
    ];
}

function aggregate_volume_rows($rows) {
    $totals = [];

    foreach ($rows as $row) {
        $unit = normalize_volume_unit($row['satuan'] ?? '');
        if (!isset($totals[$unit['key']])) {
            $totals[$unit['key']] = [
                'value' => 0.0,
                'unit' => $unit['label']
            ];
        }
        $totals[$unit['key']]['value'] += (float)($row['volume'] ?? 0) * $unit['factor'];
    }

    if (isset($totals['mass_kg'])) {
        $mass = ['mass_kg' => $totals['mass_kg']];
        unset($totals['mass_kg']);
        $totals = $mass + $totals;
    }

    return $totals;
}

function format_volume_amount($value, $decimals = 2) {
    $formatted = number_format((float)$value, $decimals, ',', '.');
    if ($decimals > 0) {
        $formatted = rtrim(rtrim($formatted, '0'), ',');
    }
    return $formatted;
}

function describe_volume_group($key, $group) {
    $value = (float)$group['value'];
    $unit = $group['unit'];

    if ($key === 'mass_kg') {
        if ($value >= 1000) {
            return ['amount' => format_volume_amount($value / 1000, 5), 'unit' => 'Ton'];
        }
        return ['amount' => format_volume_amount($value), 'unit' => 'Kilogram'];
    }

    return ['amount' => format_volume_amount($value), 'unit' => $unit];
}

function format_volume_group($key, $group) {
    $description = describe_volume_group($key, $group);
    return $description['amount'] . ' ' . $description['unit'];
}

function format_volume_groups($groups) {
    if (empty($groups)) {
        return '0 Kilogram';
    }

    $formatted = [];
    foreach ($groups as $key => $group) {
        $formatted[] = format_volume_group($key, $group);
    }

    return implode(' · ', $formatted);
}

function compare_volume_groups($periodA, $periodB) {
    $keys = array_unique(array_merge(array_keys($periodA), array_keys($periodB)));
    $changes = [];
    $directions = [];

    foreach ($keys as $key) {
        $valueA = (float)($periodA[$key]['value'] ?? 0);
        $valueB = (float)($periodB[$key]['value'] ?? 0);
        $unit = $periodA[$key]['unit'] ?? $periodB[$key]['unit'];

        if ($valueA == 0.0) {
            $label = $valueB == 0.0 ? '0,0%' : 'Baru';
            $directions[] = 1;
        } else {
            $change = (($valueB - $valueA) / abs($valueA)) * 100;
            $label = sprintf('%+.1f%%', $change);
            $directions[] = $change < 0 ? -1 : 1;
        }

        $changes[] = $unit . ': ' . $label;
    }

    return [
        'label' => implode(' · ', $changes),
        'direction' => !empty($directions) && count(array_unique($directions)) === 1 && $directions[0] < 0
            ? 'is-down'
            : 'is-up'
    ];
}

function build_volume_comparison_datasets($periodA, $periodB) {
    $keys = array_unique(array_merge(array_keys($periodA), array_keys($periodB)));
    $datasets = [];

    foreach ($keys as $index => $key) {
        $unit = $periodA[$key]['unit'] ?? $periodB[$key]['unit'];
        $datasets[] = [
            'label' => $key === 'mass_kg' ? 'Kilogram (ekuivalen)' : $unit,
            'data' => [
                (float)($periodA[$key]['value'] ?? 0),
                (float)($periodB[$key]['value'] ?? 0)
            ],
            'backgroundColor' => $index % 2 === 0 ? '#2563eb' : '#0f766e',
            'borderRadius' => 5,
            'barPercentage' => 0.55
        ];
    }

    return $datasets;
}
