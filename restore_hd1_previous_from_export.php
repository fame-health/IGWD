<?php

use App\Models\DialysisSession;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

require __DIR__.'/vendor/autoload.php';

$app = require __DIR__.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$exportPath = __DIR__.'/storage/app/public/temp-imports/01M00WKRCNGBH25N7A5CGY46ER.xlsx';
$mode = $argv[1] ?? 'dry-run';
$shouldApply = $mode === 'apply';

function normalize_header(string $header): string
{
    return strtolower(trim(preg_replace('/\s+/', ' ', $header)));
}

function number_or_null(mixed $value): ?float
{
    if ($value === null || $value === '') {
        return null;
    }

    return round((float) str_replace(',', '.', (string) $value), 2);
}

$spreadsheet = IOFactory::load($exportPath);
$sheet = $spreadsheet->getActiveSheet();
$highestRow = $sheet->getHighestRow();
$highestColumn = $sheet->getHighestColumn();
$headers = $sheet->rangeToArray("A1:{$highestColumn}1", null, true, true, false)[0];
$index = [];

foreach ($headers as $position => $header) {
    $index[normalize_header((string) $header)] = $position;
}

$rowsById = [];

for ($rowNumber = 2; $rowNumber <= $highestRow; $rowNumber++) {
    $values = $sheet->rangeToArray("A{$rowNumber}:{$highestColumn}{$rowNumber}", null, true, true, false)[0];
    $id = (int) ($values[$index['id']] ?? 0);

    if ($id <= 0) {
        continue;
    }

    $rowsById[$id] = [
        'previous_post_hd_weight' => number_or_null($values[$index['bb pre hd terakhir']] ?? null),
        'idwg_kg' => number_or_null($values[$index['idwg (kg)']] ?? null),
        'idwg_percent' => number_or_null($values[$index['idwg (%)']] ?? null),
        'risk_category' => trim((string) ($values[$index['risiko']] ?? '')) ?: null,
        'name' => trim((string) ($values[$index['pasien']] ?? '')),
    ];
}

$sessions = DialysisSession::query()
    ->with('patient:id,name')
    ->whereNull('previous_post_hd_weight')
    ->orderBy('session_date')
    ->orderBy('id')
    ->get();

$matched = [];
$unmatched = [];

foreach ($sessions as $session) {
    if (isset($rowsById[$session->id]) && $rowsById[$session->id]['previous_post_hd_weight'] !== null) {
        $matched[] = [$session, $rowsById[$session->id]];
    } else {
        $unmatched[] = $session;
    }
}

echo 'database='.DB::connection()->getDatabaseName().PHP_EOL;
echo 'export_rows='.count($rowsById).PHP_EOL;
echo 'empty_previous_sessions='.$sessions->count().PHP_EOL;
echo 'matched_by_id='.count($matched).PHP_EOL;
echo 'unmatched='.count($unmatched).PHP_EOL;

echo 'Sample matched:'.PHP_EOL;
foreach (array_slice($matched, 0, 10) as [$session, $row]) {
    echo '- #'.$session->id.' '.$session->session_date->toDateString().' '.$session->patient?->name.' prev='.$row['previous_post_hd_weight'].' old_idwg='.$row['idwg_kg'].' old_risk='.$row['risk_category'].PHP_EOL;
}

if ($unmatched) {
    echo 'Unmatched:'.PHP_EOL;
    foreach ($unmatched as $session) {
        echo '- #'.$session->id.' '.$session->session_date->toDateString().' '.$session->patient?->name.' pre='.$session->current_pre_hd_weight.' post='.$session->current_post_hd_weight.PHP_EOL;
    }
}

if (! $shouldApply) {
    exit(0);
}

DB::transaction(function () use ($matched): void {
    foreach ($matched as [$session, $row]) {
        $session->previous_post_hd_weight = $row['previous_post_hd_weight'];
        $session->save();
    }
});

echo 'updated='.count($matched).PHP_EOL;
