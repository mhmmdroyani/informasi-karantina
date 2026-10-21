<?php

function normalize_raw_excel_header($value)
{
    return strtolower(preg_replace('/[^a-z0-9]/i', '', trim((string)$value)));
}

function raw_excel_unique_values($values)
{
    $unique = [];
    foreach ($values as $value) {
        $value = trim((string)$value);
        if ($value !== '') {
            $unique[strtolower($value)] = $value;
        }
    }
    return array_values($unique);
}

function raw_excel_group_key($commodity, $unit)
{
    $normalizedCommodity = strtolower(preg_replace('/\s+/', ' ', trim((string)$commodity)));
    $normalizedUnit = strtolower(preg_replace('/\s+/', ' ', trim((string)$unit)));

    return $normalizedCommodity . "\0" . $normalizedUnit;
}

function raw_excel_required_columns()
{
    return [
        'klasifikasi' => 'klasifikasi',
        'komoditas' => 'komoditas',
        'volp1' => 'volume',
        'satuanlain' => 'satuan',
        'hargabarangrp' => 'nilai',
        'daerahasal' => 'daerah_asal',
        'daerahtujuan' => 'daerah_tujuan',
        'negaratujuan' => 'negara_tujuan',
    ];
}

function ensure_monthly_raw_columns($pdo)
{
    foreach (['klasifikasi', 'daerah_asal', 'daerah_tujuan'] as $column) {
        $exists = $pdo->query("SHOW COLUMNS FROM ekspor_komoditas LIKE " . $pdo->quote($column))->fetchAll();
        if (empty($exists)) {
            $sql = match ($column) {
                'klasifikasi' => 'ALTER TABLE ekspor_komoditas ADD COLUMN klasifikasi VARCHAR(255) NULL AFTER kategori',
                'daerah_asal' => 'ALTER TABLE ekspor_komoditas ADD COLUMN daerah_asal TEXT NULL AFTER nilai_ekspor',
                'daerah_tujuan' => 'ALTER TABLE ekspor_komoditas ADD COLUMN daerah_tujuan TEXT NULL AFTER daerah_asal',
            };
            $pdo->exec($sql);
        }
    }
}

function aggregate_raw_excel_rows($rows)
{
    $required = raw_excel_required_columns();

    $headerRow = null;
    $columns = [];
    foreach ($rows as $rowIndex => $row) {
        $candidate = [];
        foreach ($row as $columnIndex => $value) {
            $key = normalize_raw_excel_header((string)$value);
            if (isset($required[$key])) {
                $candidate[$required[$key]] = $columnIndex;
            }
        }
        if (count($candidate) >= count($required)) {
            $headerRow = $rowIndex;
            $columns = $candidate;
            break;
        }
    }
    if ($headerRow === null) {
        throw new RuntimeException('Header Excel tidak cocok. Pastikan kolom Klasifikasi, Komoditas, Vol P1, Satuan Lain, Harga Barang (Rp), Daerah Asal, Daerah Tujuan, Negara Tujuan ada di Excel.');
    }

    $groups = [];
    for ($i = $headerRow + 1; $i < count($rows); $i++) {
        $row = $rows[$i];
        $nama = trim((string)($row[$columns['komoditas']] ?? ''));
        if ($nama === '' || preg_match('/^(total|jumlah|subtotal)$/i', $nama)) {
            continue;
        }

        $volume = clean_number($row[$columns['volume']] ?? 0);
        $nilai = clean_number($row[$columns['nilai']] ?? 0);
        $unit = trim((string)($row[$columns['satuan']] ?? '')) ?: 'Tidak diketahui';
        $klasifikasi = trim((string)($row[$columns['klasifikasi']] ?? ''));
        $daerahAsal = trim((string)($row[$columns['daerah_asal']] ?? ''));
        $daerahTujuan = trim((string)($row[$columns['daerah_tujuan']] ?? ''));
        $negaraTujuan = trim((string)($row[$columns['negara_tujuan']] ?? ''));

        $key = raw_excel_group_key($nama, $unit);
        if (!isset($groups[$key])) {
            $groups[$key] = [
                'klasifikasi' => $klasifikasi,
                'nama_komoditas' => $nama,
                'frekuensi' => 0,
                'volume' => 0.0,
                'satuan' => $unit,
                'nilai_ekspor' => 0.0,
                'daerah_asal' => [],
                'daerah_tujuan' => [],
                'negara_tujuan' => []
            ];
        }
        $groups[$key]['frekuensi']++;
        $groups[$key]['volume'] += $volume;
        $groups[$key]['nilai_ekspor'] += $nilai;
        if ($daerahAsal !== '') $groups[$key]['daerah_asal'][] = $daerahAsal;
        if ($daerahTujuan !== '') $groups[$key]['daerah_tujuan'][] = $daerahTujuan;
        if ($negaraTujuan !== '') $groups[$key]['negara_tujuan'][] = $negaraTujuan;
    }

    $result = [];
    foreach ($groups as $group) {
        $result[] = [
            'klasifikasi' => $group['klasifikasi'],
            'nama_komoditas' => $group['nama_komoditas'],
            'frekuensi' => (int)$group['frekuensi'],
            'volume' => (float)$group['volume'],
            'satuan' => $group['satuan'],
            'nilai_ekspor' => (float)$group['nilai_ekspor'],
            'daerah_asal' => implode(', ', raw_excel_unique_values($group['daerah_asal'])),
            'daerah_tujuan' => implode(', ', raw_excel_unique_values($group['daerah_tujuan'])),
            'negara_tujuan' => implode(', ', raw_excel_unique_values($group['negara_tujuan']))
        ];
    }

    return $result;
}

function aggregate_raw_excel_sheet($sheet)
{
    $required = raw_excel_required_columns();
    $headerRow = null;
    $columns = [];
    $rows = [];
    $highestDataRow = $sheet->getHighestDataRow();

    foreach ($sheet->getRowIterator(1, $highestDataRow) as $row) {
        $rowIndex = $row->getRowIndex();

        if ($headerRow === null) {
            $headerValues = [];
            $candidate = [];
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(true);
            foreach ($cellIterator as $cell) {
                $columnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($cell->getColumn()) - 1;
                $value = $cell->getCalculatedValue();
                $headerValues[$columnIndex] = $value;
                $key = normalize_raw_excel_header((string)$value);
                if (isset($required[$key])) {
                    $candidate[$required[$key]] = $columnIndex;
                }
            }

            if (count($candidate) >= count($required)) {
                $headerRow = $rowIndex;
                $columns = $candidate;
                $rows[] = $headerValues;
            }
            continue;
        }

        $rowValues = [];
        foreach ($columns as $columnIndex) {
            $coordinate = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columnIndex + 1) . $rowIndex;
            $rowValues[$columnIndex] = $sheet->getCell($coordinate)->getCalculatedValue();
        }
        $rows[] = $rowValues;
    }

    return aggregate_raw_excel_rows($rows);
}

function merge_raw_excel_entries($existingEntries, $newEntries)
{
    $merged = [];
    foreach (array_merge($existingEntries, $newEntries) as $entry) {
        $unit = trim((string)$entry['satuan']) ?: 'Tidak diketahui';
        $key = raw_excel_group_key($entry['nama_komoditas'], $unit);
        if (!isset($merged[$key])) {
            $merged[$key] = [
                'klasifikasi' => (string)($entry['klasifikasi'] ?? ''),
                'nama_komoditas' => (string)$entry['nama_komoditas'],
                'frekuensi' => (int)$entry['frekuensi'],
                'volume' => (float)$entry['volume'],
                'satuan' => $unit,
                'nilai_ekspor' => (float)$entry['nilai_ekspor'],
                'daerah_asal' => is_array($entry['daerah_asal']) ? $entry['daerah_asal'] : preg_split('/\s*(?:,|;|\||\n)\s*/', (string)$entry['daerah_asal']),
                'daerah_tujuan' => is_array($entry['daerah_tujuan']) ? $entry['daerah_tujuan'] : preg_split('/\s*(?:,|;|\||\n)\s*/', (string)$entry['daerah_tujuan']),
                'negara_tujuan' => is_array($entry['negara_tujuan']) ? $entry['negara_tujuan'] : preg_split('/\s*(?:,|;|\||\n)\s*/', (string)$entry['negara_tujuan'])
            ];
            continue;
        }

        $merged[$key]['frekuensi'] += (int)$entry['frekuensi'];
        $merged[$key]['volume'] += (float)$entry['volume'];
        $merged[$key]['nilai_ekspor'] += (float)$entry['nilai_ekspor'];
        foreach (['daerah_asal', 'daerah_tujuan', 'negara_tujuan'] as $field) {
            $values = is_array($entry[$field]) ? $entry[$field] : preg_split('/\s*(?:,|;|\||\n)\s*/', (string)$entry[$field]);
            $merged[$key][$field] = array_merge($merged[$key][$field], $values);
        }
        if ($merged[$key]['klasifikasi'] === '') {
            $merged[$key]['klasifikasi'] = (string)($entry['klasifikasi'] ?? '');
        }
    }

    foreach ($merged as &$entry) {
        foreach (['daerah_asal', 'daerah_tujuan', 'negara_tujuan'] as $field) {
            $entry[$field] = implode(', ', raw_excel_unique_values($entry[$field]));
        }
    }
    unset($entry);

    return array_values($merged);
}

function insert_raw_aggregated_entries($pdo, $jenis, $kategori, $periode, $entries, $mode = 'append')
{
    ensure_monthly_raw_columns($pdo);

    $start = $periode . '-01';
    $end = date('Y-m-t', strtotime($start));
    if ($mode !== 'replace') {
        $existing = $pdo->prepare('SELECT klasifikasi, nama_komoditas, frekuensi, volume, satuan, nilai_ekspor, daerah_asal, daerah_tujuan, negara_tujuan FROM ekspor_komoditas WHERE jenis_kegiatan = ? AND kategori = ? AND periode = ?');
        $existing->execute([$jenis, $kategori, $periode]);
        $entries = merge_raw_excel_entries($existing->fetchAll(PDO::FETCH_ASSOC), $entries);
    }

    $pdo->beginTransaction();
    try {
        if ($mode === 'replace' || !empty($entries)) {
            $delete = $pdo->prepare('DELETE FROM ekspor_komoditas WHERE jenis_kegiatan = ? AND kategori = ? AND periode = ?');
            $delete->execute([$jenis, $kategori, $periode]);
        }

        $insert = $pdo->prepare('INSERT INTO ekspor_komoditas
            (jenis_kegiatan, kategori, klasifikasi, nama_komoditas, frekuensi, volume, satuan, nilai_ekspor, daerah_asal, daerah_tujuan, negara_tujuan, periode, tanggal_mulai, tanggal_selesai, is_published)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0)');

        foreach ($entries as $entry) {
            $insert->execute([
                $jenis,
                $kategori,
                $entry['klasifikasi'],
                $entry['nama_komoditas'],
                $entry['frekuensi'],
                $entry['volume'],
                $entry['satuan'],
                $entry['nilai_ekspor'],
                $entry['daerah_asal'],
                $entry['daerah_tujuan'],
                $entry['negara_tujuan'],
                $periode,
                $start,
                $end
            ]);
        }

        $pdo->commit();
    } catch (Exception $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $exception;
    }

    return count($entries);
}

function handle_raw_excel_upload($pdo, $kategori, array $databaseConfig)
{
    require_csrf();

    $jenis = $_POST['jenis_kegiatan'] ?? 'domestik_masuk';
    if (!in_array($jenis, ['ekspor', 'impor', 'domestik_keluar', 'domestik_masuk'], true)) {
        $jenis = 'domestik_masuk';
    }
    $periode = trim((string)($_POST['periode'] ?? ''));
    $mode = ($_POST['mode'] ?? 'append') === 'replace' ? 'replace' : 'append';
    $error = '';

    if (!preg_match('/^(\d{4})-(\d{2})$/', $periode, $matches)
        || !checkdate((int)$matches[2], 1, (int)$matches[1])) {
        $error = 'Pilih satu bulan dan tahun data yang valid.';
    } elseif (!isset($_FILES['file_excel']) || $_FILES['file_excel']['error'] !== UPLOAD_ERR_OK) {
        $error = 'Pilih file Excel mentah yang valid untuk diunggah.';
    } else {
        $extension = strtolower(pathinfo($_FILES['file_excel']['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, ['xlsx', 'xls', 'csv'], true)) {
            $error = 'Format file tidak didukung. Gunakan Excel .xlsx, .xls, atau .csv.';
        } else {
            try {
                $filePath = $_FILES['file_excel']['tmp_name'];
                $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($filePath);
                $sheetNames = $reader->listWorksheetNames($filePath);
                $sheetNameToLoad = $sheetNames[0] ?? null;
                foreach ($sheetNames as $sheetName) {
                    if (strcasecmp($sheetName, 'Ops') === 0) {
                        $sheetNameToLoad = $sheetName;
                        break;
                    }
                }
                if ($sheetNameToLoad === null) {
                    throw new RuntimeException('File Excel tidak memiliki sheet yang dapat dibaca.');
                }
                $reader->setReadDataOnly(true);
                $reader->setReadEmptyCells(false);
                $reader->setLoadSheetsOnly([$sheetNameToLoad]);
                $spreadsheet = $reader->load($filePath);
                $entries = aggregate_raw_excel_sheet($spreadsheet->getSheet(0));
                if (empty($entries)) {
                    throw new RuntimeException('Tidak ditemukan baris data komoditas pada Excel.');
                }
                unset($spreadsheet, $reader);
                $pdo = create_database_connection($databaseConfig);
                $count = insert_raw_aggregated_entries($pdo, $jenis, $kategori, $periode, $entries, $mode);
                $message = 'Berhasil merekap ' . $count . ' komoditas untuk ' . (format_label_periode_kode($periode) ?: $periode) . '.';
            } catch (Throwable $exception) {
                error_log('SILAKOM raw spreadsheet import failed: ' . $exception->getMessage());
                $error = $exception->getMessage();
            }
        }
    }

    $query = ['jenis' => $jenis];
    if ($error !== '') {
        $query['error'] = $error;
    } else {
        $query['msg'] = $message;
    }
    header('Location: ' . basename($_SERVER['PHP_SELF']) . '?' . http_build_query($query));
    exit;
}
