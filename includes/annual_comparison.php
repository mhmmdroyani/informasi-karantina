<?php

require_once __DIR__ . '/volume_units.php';

function published_comparison_years(array $periods): array
{
    $years = [];
    foreach ($periods as $period) {
        if (
            !is_string($period)
            || !preg_match('/^(\d{4})-(0[1-9]|1[0-2])$/', $period, $matches)
        ) {
            continue;
        }

        $years[] = $matches[1];
    }

    $years = array_values(array_unique($years));
    sort($years, SORT_STRING);
    return $years;
}

function aggregate_comparison_months(array $years, array $rows): array
{
    $monthly = [];
    foreach ($years as $year) {
        $monthly[(string)$year] = [
            'nilai' => array_fill(1, 12, 0.0),
            'volume' => array_fill(1, 12, 0.0),
            'frekuensi' => array_fill(1, 12, 0),
            'negara' => array_fill(1, 12, [])
        ];
    }

    foreach ($rows as $row) {
        $period = (string)($row['periode'] ?? '');
        if (!preg_match('/^(\d{4})-(0[1-9]|1[0-2])$/', $period, $matches)) {
            continue;
        }

        $year = $matches[1];
        $month = (int)$matches[2];
        if (!isset($monthly[$year])) {
            continue;
        }

        $monthly[$year]['nilai'][$month] += (float)($row['nilai_ekspor'] ?? 0);
        $monthly[$year]['volume'][$month] += normalize_compatible_volume_unit(
            $row['volume'] ?? 0,
            $row['satuan'] ?? ''
        )['volume'];
        $monthly[$year]['frekuensi'][$month] += (int)($row['frekuensi'] ?? 0);

        $parts = preg_split('/\s*(?:,|;|\/|\|)\s*/', (string)($row['lokasi_tujuan'] ?? ''));
        foreach ($parts as $country) {
            $country = trim($country);
            if ($country !== '') {
                $monthly[$year]['negara'][$month][strtolower($country)] = $country;
            }
        }
    }

    foreach ($monthly as &$yearMetrics) {
        foreach ($yearMetrics['negara'] as &$countriesByMonth) {
            $countriesByMonth = count($countriesByMonth);
        }
        unset($countriesByMonth);
    }
    unset($yearMetrics);

    return $monthly;
}
