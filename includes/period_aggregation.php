<?php

require_once __DIR__ . '/volume_units.php';

function aggregate_monthly_period_rows(array $rows): array
{
    $groups = [];
    foreach ($rows as $row) {
        $normalizedVolume = normalize_compatible_volume_unit($row['volume'] ?? 0, $row['satuan'] ?? '');
        $commodity = trim((string)($row['nama_komoditas'] ?? ''));
        $commodityKey = strtolower(preg_replace('/\s+/', ' ', $commodity));
        $unitKey = strtolower(preg_replace('/\s+/', ' ', $normalizedVolume['unit']));
        $groupKey = $commodityKey . "\0" . $unitKey;

        if (!isset($groups[$groupKey])) {
            $groups[$groupKey] = [
                'klasifikasi' => (string)($row['klasifikasi'] ?? ''),
                'nama_komoditas' => $commodity,
                'nama_komoditas_lokal' => (string)($row['nama_komoditas_lokal'] ?? ''),
                'frekuensi' => 0,
                'volume' => 0.0,
                'satuan' => $normalizedVolume['unit'],
                'nilai_ekspor' => 0.0,
                'daerah_asal' => [],
                'daerah_tujuan' => [],
                'negara_tujuan' => [],
                'source_count' => 0,
                'published_count' => 0,
            ];
        }

        $group =& $groups[$groupKey];
        $group['frekuensi'] += (int)($row['frekuensi'] ?? 0);
        $group['volume'] += $normalizedVolume['volume'];
        $group['nilai_ekspor'] += (float)($row['nilai_ekspor'] ?? 0);
        $group['source_count']++;
        $group['published_count'] += (int)($row['is_published'] ?? 0) === 1 ? 1 : 0;
        if ($group['klasifikasi'] === '') {
            $group['klasifikasi'] = (string)($row['klasifikasi'] ?? '');
        }
        if ($group['nama_komoditas_lokal'] === '' && !empty($row['nama_komoditas_lokal'])) {
            $group['nama_komoditas_lokal'] = (string)$row['nama_komoditas_lokal'];
        }
        foreach (['daerah_asal', 'daerah_tujuan', 'negara_tujuan'] as $field) {
            $parts = preg_split('/\s*(?:,|;|\/|\|)\s*/', (string)($row[$field] ?? ''));
            foreach ($parts as $part) {
                $part = trim($part);
                if ($part !== '') {
                    $group[$field][strtolower($part)] = $part;
                }
            }
        }
        unset($group);
    }

    $aggregated = [];
    foreach ($groups as $group) {
        foreach (['daerah_asal', 'daerah_tujuan', 'negara_tujuan'] as $field) {
            $group[$field] = implode(', ', array_values($group[$field]));
        }
        $group['publication_status'] = $group['published_count'] === 0
            ? 'Draft'
            : ($group['published_count'] === $group['source_count'] ? 'Published' : 'Campuran');
        $group['is_published'] = $group['published_count'] === $group['source_count'] ? 1 : 0;
        unset($group['published_count']);
        $aggregated[] = $group;
    }

    return $aggregated;
}
