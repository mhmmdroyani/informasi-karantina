<?php

function parse_non_negative_number($value, $maxDecimals = 2) {
    if (is_int($value) || is_float($value)) {
        $number = (float)$value;
    } else {
        if (!is_string($value)) {
            return null;
        }
        $value = trim((string)$value);
        if ($value === '' || $value === '-') {
            return 0.0;
        }

        $value = preg_replace('/^Rp\s*/i', '', $value);
        $value = preg_replace('/\s+/', '', $value);
        if ($value === '' || !preg_match('/^\d[\d.,]*$/', $value)) {
            return null;
        }

        $commaPosition = strrpos($value, ',');
        $dotPosition = strrpos($value, '.');
        $decimalSeparator = null;
        $thousandsSeparator = null;

        if ($commaPosition !== false && $dotPosition !== false) {
            $decimalSeparator = $commaPosition > $dotPosition ? ',' : '.';
            $thousandsSeparator = $decimalSeparator === ',' ? '.' : ',';
        } elseif ($commaPosition !== false || $dotPosition !== false) {
            $separator = $commaPosition !== false ? ',' : '.';
            $parts = explode($separator, $value);
            if (count($parts) > 2) {
                if (!preg_match('/^\d{1,3}(?:' . preg_quote($separator, '/') . '\d{3})+$/', $value)) {
                    return null;
                }
                $thousandsSeparator = $separator;
            } else {
                $fractionLength = strlen($parts[1]);
                if ($fractionLength > 0 && $fractionLength <= $maxDecimals) {
                    $decimalSeparator = $separator;
                } elseif ($fractionLength === 3 && preg_match('/^\d{1,3}(?:' . preg_quote($separator, '/') . '\d{3})+$/', $value)) {
                    $thousandsSeparator = $separator;
                } else {
                    return null;
                }
            }
        }

        if ($thousandsSeparator !== null) {
            $integerPart = $decimalSeparator === null
                ? $value
                : substr($value, 0, strrpos($value, $decimalSeparator));
            if (!preg_match('/^\d{1,3}(?:' . preg_quote($thousandsSeparator, '/') . '\d{3})+$/', $integerPart)) {
                return null;
            }
            $value = str_replace($thousandsSeparator, '', $value);
        }
        if ($decimalSeparator !== null) {
            $decimalPosition = strrpos($value, $decimalSeparator);
            $fraction = substr($value, $decimalPosition + 1);
            if ($fraction === '' || strlen($fraction) > $maxDecimals) {
                return null;
            }
            $value = substr($value, 0, $decimalPosition) . '.' . $fraction;
        }

        if (!is_numeric($value)) {
            return null;
        }
        $number = (float)$value;
    }

    if (!is_finite($number) || $number < 0 || round($number, $maxDecimals) !== $number) {
        return null;
    }

    return $number;
}

function parse_non_negative_integer($value) {
    $number = parse_non_negative_number($value, 0);
    if ($number === null || $number > PHP_INT_MAX) {
        return null;
    }

    return (int)$number;
}

function parse_positive_integer_ids($values) {
    if (!is_array($values)) {
        return [];
    }

    $ids = [];
    foreach ($values as $value) {
        if (!is_int($value) && !is_string($value)) {
            continue;
        }
        $id = filter_var($value, FILTER_VALIDATE_INT);
        if ($id !== false && $id > 0) {
            $ids[$id] = $id;
        }
    }

    return array_values($ids);
}
