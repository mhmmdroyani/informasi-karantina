<?php

function normalize_compatible_volume_unit($volume, $unit)
{
    $volume = (float)$volume;
    $unit = trim((string)$unit);
    $normalizedUnit = strtolower(preg_replace('/\s+/', ' ', $unit));

    if (in_array($normalizedUnit, ['g', 'gr', 'gram', 'grams'], true)) {
        return ['volume' => $volume / 1000, 'unit' => 'Kilogram'];
    }

    if (in_array($normalizedUnit, ['kg', 'kilo', 'kilogram', 'kilograms'], true)) {
        return ['volume' => $volume, 'unit' => 'Kilogram'];
    }

    if (in_array($normalizedUnit, ['ton', 'tonne', 'tonnes'], true)) {
        return ['volume' => $volume * 1000, 'unit' => 'Kilogram'];
    }

    return ['volume' => $volume, 'unit' => $unit !== '' ? $unit : 'Tidak diketahui'];
}
