<?php

require_once __DIR__ . '/../includes/input_validation.php';

function assert_validation_result($expected, $actual, $message) {
    if ($expected !== $actual) {
        fwrite(STDERR, $message . ': expected ' . var_export($expected, true) . ', got ' . var_export($actual, true) . PHP_EOL);
        exit(1);
    }
}

assert_validation_result(1250.5, parse_non_negative_number('1.250,50'), 'Indonesian decimal and grouping');
assert_validation_result(1250.5, parse_non_negative_number('1,250.50'), 'US decimal and grouping');
assert_validation_result(1234.0, parse_non_negative_number('1.234'), 'Indonesian dot thousands grouping');
assert_validation_result(1234.0, parse_non_negative_number('1,234'), 'Three-digit thousands grouping');
assert_validation_result(12.5, parse_non_negative_number('12,5'), 'Single decimal digit');
assert_validation_result(0.0, parse_non_negative_number('-'), 'Excel dash as zero');
assert_validation_result(null, parse_non_negative_number('-2'), 'Negative value rejected');
assert_validation_result(null, parse_non_negative_number('12abc'), 'Non-numeric value rejected');
assert_validation_result(null, parse_non_negative_number(['12']), 'Array input rejected');
assert_validation_result(null, parse_non_negative_number('1,234,56'), 'Malformed grouping rejected');
assert_validation_result(null, parse_non_negative_number('12.3456'), 'Precision-over-two decimal rejected');
assert_validation_result(1250, parse_non_negative_integer('1,250'), 'Grouped integer accepted');
assert_validation_result(1250, parse_non_negative_integer('1.250'), 'Localized grouped integer accepted');
assert_validation_result(null, parse_non_negative_integer('12,5'), 'Fractional integer rejected');
assert_validation_result(null, parse_non_negative_integer('-1'), 'Negative integer rejected');
assert_validation_result([3, 4], parse_positive_integer_ids(['3', '3', '0', '-1', '4', ['5']]), 'Positive ID list normalization');

echo "Input validation boundary tests passed." . PHP_EOL;
