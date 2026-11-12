<?php

use PhpOffice\PhpSpreadsheet\IOFactory;

require __DIR__.'/vendor/autoload.php';

$paths = array_merge(
    glob(__DIR__.'/storage/app/public/temp-imports/*.xlsx') ?: [],
    glob(__DIR__.'/storage/app/private/temp-imports/*.xlsx') ?: []
);

foreach ($paths as $path) {
    echo 'FILE '.$path.PHP_EOL;

    try {
        $spreadsheet = IOFactory::load($path);
    } catch (Throwable $e) {
        echo 'ERROR '.$e->getMessage().PHP_EOL;
        continue;
    }

    foreach ($spreadsheet->getWorksheetIterator() as $sheet) {
        echo 'SHEET '.$sheet->getTitle().' '.$sheet->getHighestRow().'x'.$sheet->getHighestColumn().PHP_EOL;
        $highestRow = min($sheet->getHighestRow(), 8);
        $highestColumn = $sheet->getHighestColumn();

        for ($row = 1; $row <= $highestRow; $row++) {
            $values = [];

            foreach ($sheet->rangeToArray("A{$row}:{$highestColumn}{$row}", null, true, true, false)[0] as $value) {
                $values[] = trim((string) $value);
            }

            echo $row.'|'.implode("\t", $values).PHP_EOL;
        }
    }

    echo PHP_EOL;
}
