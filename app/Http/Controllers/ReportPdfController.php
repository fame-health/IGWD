<?php

namespace App\Http\Controllers;

use App\Models\DailyMonitoring;
use App\Models\DialysisSession;
use App\Models\Patient;
use App\Models\RiskAlert;
use App\Support\SimplePdfDocument;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;

class ReportPdfController extends Controller
{
    public function __invoke(Request $request, string $report): Response
    {
        $definition = match ($report) {
            'dashboard' => $this->dashboardReport(),
            'daily-monitoring' => $this->dailyMonitoringReport($request),
            'dialysis-sessions' => $this->dialysisSessionReport($request),
            'risk-patients' => $this->riskPatientReport($request),
            'risk-alerts' => $this->riskAlertReport($request),
            default => abort(404),
        };

        return response($this->renderPdf($definition), 200, [
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Content-Disposition' => 'attachment; filename="'.$definition['filename'].'"',
            'Content-Type' => 'application/pdf',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function dashboardReport(): array
    {
        $today = now()->toDateString();
        $alerts = RiskAlert::query()
            ->with('patient')
            ->latest('alert_date')
            ->latest('id')
            ->limit(40)
            ->get();

        return [
            'title' => 'Ringkasan Dashboard IDWG',
            'subtitle' => 'Ringkasan operasional dan alert terbaru',
            'filename' => $this->filename('laporan-dashboard'),
            'summary' => [
                'Total Pasien' => Patient::count(),
                'Pasien Aktif' => Patient::where('patient_status', 'Aktif')->count(),
                'Monitoring Hari Ini' => DailyMonitoring::whereDate('monitoring_date', $today)->count(),
                'Alert Baru' => RiskAlert::where('status', 'Baru')->count(),
                'Alert Tinggi/Darurat' => RiskAlert::whereIn('alert_level', ['Tinggi', 'Darurat'])->count(),
                'Sesi HD Hari Ini' => DialysisSession::whereDate('session_date', $today)->count(),
            ],
            'columns' => [
                ['label' => 'Tanggal', 'width' => 0.10],
                ['label' => 'No RM', 'width' => 0.11],
                ['label' => 'Pasien', 'width' => 0.18],
                ['label' => 'Level', 'width' => 0.10],
                ['label' => 'Jenis', 'width' => 0.19],
                ['label' => 'Status', 'width' => 0.10],
                ['label' => 'Judul', 'width' => 0.22],
            ],
            'rows' => $alerts->map(fn (RiskAlert $alert): array => [
                $this->dateValue($alert->alert_date),
                $alert->patient?->medical_record_number ?? '-',
                $alert->patient?->name ?? '-',
                $alert->alert_level ?? '-',
                $alert->alert_type ?? '-',
                $alert->status ?? '-',
                $alert->title ?? '-',
            ])->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function dailyMonitoringReport(Request $request): array
    {
        $data = tap(DailyMonitoring::query()->with('patient'), fn (Builder $query) => $this->applyDateFilters($query, $request, 'monitoring_date'))
            ->latest('monitoring_date')
            ->latest('id')
            ->get();

        return [
            'title' => 'Laporan Monitoring Harian',
            'subtitle' => $this->periodLabel($request),
            'filename' => $this->filename('laporan-monitoring-harian'),
            'summary' => [
                'Total Monitoring' => $data->count(),
                'Cairan Melebihi Batas' => $data->where('fluid_status', 'Melebihi Batas')->count(),
                'Risiko Tinggi/Darurat' => $data->whereIn('risk_status', ['Tinggi', 'Darurat'])->count(),
                'Rata-rata Kenaikan BB' => $this->number($data->avg('daily_weight_gain_kg'), 2, ' kg'),
            ],
            'columns' => [
                ['label' => 'Tanggal', 'width' => 0.09],
                ['label' => 'No RM', 'width' => 0.10],
                ['label' => 'Pasien', 'width' => 0.16],
                ['label' => 'BB', 'width' => 0.08],
                ['label' => 'Kenaikan', 'width' => 0.09],
                ['label' => 'Masuk', 'width' => 0.09],
                ['label' => 'Keluar', 'width' => 0.09],
                ['label' => 'Balance', 'width' => 0.09],
                ['label' => 'Status Cairan', 'width' => 0.11],
                ['label' => 'Risiko', 'width' => 0.10],
            ],
            'rows' => $data->map(fn (DailyMonitoring $record): array => [
                $this->dateValue($record->monitoring_date),
                $record->patient?->medical_record_number ?? '-',
                $record->patient?->name ?? '-',
                $this->number($record->today_weight, 2, ' kg'),
                $this->number($record->daily_weight_gain_kg, 2, ' kg'),
                $this->number($record->fluid_intake_ml, 0, ' ml'),
                $this->number($record->fluid_output_ml, 0, ' ml'),
                $this->number($record->fluid_difference_ml, 0, ' ml'),
                $record->fluid_status ?? '-',
                $record->risk_status ?? '-',
            ])->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function dialysisSessionReport(Request $request): array
    {
        $data = tap(DialysisSession::query()->with('patient'), fn (Builder $query) => $this->applyDateFilters($query, $request, 'session_date'))
            ->latest('session_date')
            ->latest('id')
            ->get();

        return [
            'title' => 'Laporan Sesi HD',
            'subtitle' => $this->periodLabel($request),
            'filename' => $this->filename('laporan-sesi-hd'),
            'summary' => [
                'Total Sesi' => $data->count(),
                'Rata-rata IDWG' => $this->number($data->avg('idwg_percent'), 2, ' %'),
                'Risiko Tinggi/Darurat' => $data->whereIn('risk_category', ['Tinggi', 'Darurat'])->count(),
                'Total Target UF' => $this->number($data->sum('target_ultrafiltration'), 2),
            ],
            'columns' => [
                ['label' => 'Tanggal', 'width' => 0.10],
                ['label' => 'No RM', 'width' => 0.10],
                ['label' => 'Pasien', 'width' => 0.18],
                ['label' => 'Shift', 'width' => 0.08],
                ['label' => 'BB Pre', 'width' => 0.09],
                ['label' => 'IDWG kg', 'width' => 0.09],
                ['label' => 'IDWG %', 'width' => 0.09],
                ['label' => 'Risiko', 'width' => 0.10],
                ['label' => 'UF', 'width' => 0.08],
                ['label' => 'Durasi', 'width' => 0.09],
            ],
            'rows' => $data->map(fn (DialysisSession $record): array => [
                $this->dateValue($record->session_date),
                $record->patient?->medical_record_number ?? '-',
                $record->patient?->name ?? '-',
                $record->shift ?? '-',
                $this->number($record->current_pre_hd_weight, 2, ' kg'),
                $this->number($record->idwg_kg, 2, ' kg'),
                $this->number($record->idwg_percent, 2, ' %'),
                $record->risk_category ?? '-',
                $this->number($record->target_ultrafiltration, 2),
                $this->number($record->hd_duration_minutes, 0, ' menit'),
            ])->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function riskPatientReport(Request $request): array
    {
        $alerts = tap(
            RiskAlert::query()
                ->with('patient')
                ->whereIn('alert_level', ['Tinggi', 'Darurat'])
                ->whereNotNull('patient_id'),
            fn (Builder $query) => $this->applyDateFilters($query, $request, 'alert_date')
        )
            ->latest('alert_date')
            ->latest('id')
            ->get();

        $patients = $alerts
            ->groupBy('patient_id')
            ->map(function (Collection $patientAlerts): array {
                /** @var RiskAlert $latest */
                $latest = $patientAlerts->sortByDesc('alert_date')->first();

                return [
                    $latest->patient?->medical_record_number ?? '-',
                    $latest->patient?->name ?? '-',
                    (string) $patientAlerts->count(),
                    $patientAlerts->contains('alert_level', 'Darurat') ? 'Darurat' : 'Tinggi',
                    $this->dateValue($latest->alert_date),
                    $latest->alert_type ?? '-',
                    $latest->status ?? '-',
                ];
            })
            ->values();

        return [
            'title' => 'Laporan Pasien Risiko',
            'subtitle' => $this->periodLabel($request),
            'filename' => $this->filename('laporan-pasien-risiko'),
            'summary' => [
                'Total Pasien Risiko' => $patients->count(),
                'Total Alert' => $alerts->count(),
                'Alert Darurat' => $alerts->where('alert_level', 'Darurat')->count(),
                'Alert Tinggi' => $alerts->where('alert_level', 'Tinggi')->count(),
            ],
            'columns' => [
                ['label' => 'No RM', 'width' => 0.12],
                ['label' => 'Pasien', 'width' => 0.24],
                ['label' => 'Jumlah Alert', 'width' => 0.12],
                ['label' => 'Level Tertinggi', 'width' => 0.13],
                ['label' => 'Alert Terakhir', 'width' => 0.12],
                ['label' => 'Jenis Terakhir', 'width' => 0.18],
                ['label' => 'Status', 'width' => 0.09],
            ],
            'rows' => $patients->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function riskAlertReport(Request $request): array
    {
        $data = tap(RiskAlert::query()->with('patient'), fn (Builder $query) => $this->applyDateFilters($query, $request, 'alert_date'))
            ->latest('alert_date')
            ->latest('id')
            ->get();

        return [
            'title' => 'Laporan Notifikasi Risiko',
            'subtitle' => $this->periodLabel($request),
            'filename' => $this->filename('laporan-notifikasi-risiko'),
            'summary' => [
                'Total Alert' => $data->count(),
                'Alert Baru' => $data->where('status', 'Baru')->count(),
                'Alert Tinggi/Darurat' => $data->whereIn('alert_level', ['Tinggi', 'Darurat'])->count(),
                'Alert Selesai' => $data->where('status', 'Selesai')->count(),
            ],
            'columns' => [
                ['label' => 'Tanggal', 'width' => 0.09],
                ['label' => 'No RM', 'width' => 0.10],
                ['label' => 'Pasien', 'width' => 0.17],
                ['label' => 'Level', 'width' => 0.09],
                ['label' => 'Jenis', 'width' => 0.18],
                ['label' => 'Status', 'width' => 0.09],
                ['label' => 'Judul', 'width' => 0.28],
            ],
            'rows' => $data->map(fn (RiskAlert $record): array => [
                $this->dateValue($record->alert_date),
                $record->patient?->medical_record_number ?? '-',
                $record->patient?->name ?? '-',
                $record->alert_level ?? '-',
                $record->alert_type ?? '-',
                $record->status ?? '-',
                $record->title ?? '-',
            ])->all(),
        ];
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    private function renderPdf(array $definition): string
    {
        $pdf = new SimplePdfDocument;
        $pageNumber = 1;
        $y = $this->drawHeader($pdf, $definition, $pageNumber);
        $y = $this->drawSummary($pdf, $definition['summary'], $y);

        $this->drawTable($pdf, $definition, $y - 10, $pageNumber);

        return $pdf->render();
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    private function drawHeader(SimplePdfDocument $pdf, array $definition, int $pageNumber): float
    {
        $margin = 32;
        $title = $definition['title'].($pageNumber > 1 ? ' (lanjutan)' : '');

        $pdf->text($margin, $pdf->height() - 32, $title, 16, true);
        $pdf->text($margin, $pdf->height() - 49, 'Dicetak: '.now()->format('d/m/Y H:i').' WIB | '.$definition['subtitle'], 8.5, false, [0.35, 0.39, 0.45]);
        $pdf->line($margin, $pdf->height() - 62, $pdf->width() - $margin, $pdf->height() - 62);

        return $pdf->height() - 82;
    }

    /**
     * @param  array<string, scalar|null>  $summary
     */
    private function drawSummary(SimplePdfDocument $pdf, array $summary, float $y): float
    {
        $margin = 32;
        $gap = 8;
        $columns = 4;
        $cardWidth = (($pdf->width() - ($margin * 2)) - ($gap * ($columns - 1))) / $columns;
        $cardHeight = 42;
        $index = 0;

        foreach ($summary as $label => $value) {
            $column = $index % $columns;
            $row = intdiv($index, $columns);
            $x = $margin + ($column * ($cardWidth + $gap));
            $top = $y - ($row * ($cardHeight + $gap));

            $pdf->fillRect($x, $top - $cardHeight, $cardWidth, $cardHeight, [0.94, 0.97, 0.97]);
            $pdf->strokeRect($x, $top - $cardHeight, $cardWidth, $cardHeight, [0.80, 0.86, 0.86]);
            $pdf->text($x + 9, $top - 17, (string) $label, 7.5, false, [0.35, 0.39, 0.45]);
            $pdf->text($x + 9, $top - 33, (string) $value, 12, true);

            $index++;
        }

        $rows = (int) ceil(max(1, count($summary)) / $columns);

        return $y - ($rows * ($cardHeight + $gap));
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    private function drawTable(SimplePdfDocument $pdf, array $definition, float $y, int &$pageNumber): void
    {
        $rows = $definition['rows'];

        if ($rows === []) {
            $pdf->text(32, $y, 'Tidak ada data laporan.', 9.5, false, [0.35, 0.39, 0.45]);

            return;
        }

        $columns = $definition['columns'];
        $widths = $this->columnWidths($pdf, $columns);
        $y = $this->drawTableHeader($pdf, $columns, $widths, $y);

        foreach ($rows as $rowIndex => $row) {
            $wrappedCells = [];
            $maxLines = 1;

            foreach (array_values($row) as $cellIndex => $cell) {
                $lines = $this->limitLines($pdf->wrapText((string) $cell, $widths[$cellIndex] - 8, 7.2));
                $wrappedCells[] = $lines;
                $maxLines = max($maxLines, count($lines));
            }

            $rowHeight = max(20, ($maxLines * 9) + 8);

            if (($y - $rowHeight) < 32) {
                $pdf->addPage();
                $pageNumber++;
                $y = $this->drawHeader($pdf, $definition, $pageNumber);
                $y = $this->drawTableHeader($pdf, $columns, $widths, $y);
            }

            $x = 32;

            if ($rowIndex % 2 === 1) {
                $pdf->fillRect($x, $y - $rowHeight, $pdf->width() - 64, $rowHeight, [0.98, 0.99, 0.99]);
            }

            $pdf->strokeRect($x, $y - $rowHeight, $pdf->width() - 64, $rowHeight, [0.86, 0.88, 0.90], 0.35);

            foreach ($wrappedCells as $cellIndex => $lines) {
                $cellWidth = $widths[$cellIndex];
                $textY = $y - 13;

                foreach ($lines as $line) {
                    $pdf->text($x + 4, $textY, $line, 7.2, false, [0.17, 0.20, 0.24]);
                    $textY -= 9;
                }

                if ($cellIndex < count($wrappedCells) - 1) {
                    $pdf->line($x + $cellWidth, $y, $x + $cellWidth, $y - $rowHeight, [0.90, 0.91, 0.93], 0.25);
                }

                $x += $cellWidth;
            }

            $y -= $rowHeight;
        }
    }

    /**
     * @param  array<int, array{label: string, width: float}>  $columns
     * @param  array<int, float>  $widths
     */
    private function drawTableHeader(SimplePdfDocument $pdf, array $columns, array $widths, float $y): float
    {
        $x = 32;
        $height = 20;

        $pdf->fillRect($x, $y - $height, $pdf->width() - 64, $height, [0.87, 0.93, 0.93]);
        $pdf->strokeRect($x, $y - $height, $pdf->width() - 64, $height, [0.75, 0.82, 0.82], 0.45);

        foreach ($columns as $index => $column) {
            $pdf->text($x + 4, $y - 13, $column['label'], 7.4, true, [0.08, 0.18, 0.18]);
            $x += $widths[$index];
        }

        return $y - $height;
    }

    /**
     * @param  array<int, array{label: string, width: float}>  $columns
     * @return array<int, float>
     */
    private function columnWidths(SimplePdfDocument $pdf, array $columns): array
    {
        $available = $pdf->width() - 64;
        $total = array_sum(array_column($columns, 'width'));

        return array_map(
            fn (array $column): float => $available * ($column['width'] / $total),
            $columns,
        );
    }

    /**
     * @param  array<int, string>  $lines
     * @return array<int, string>
     */
    private function limitLines(array $lines): array
    {
        if (count($lines) <= 3) {
            return $lines;
        }

        $limited = array_slice($lines, 0, 3);
        $limited[2] = rtrim(substr($limited[2], 0, 34)).'...';

        return $limited;
    }

    private function applyDateFilters(Builder $query, Request $request, string $column): void
    {
        $query
            ->when($request->filled('start_date'), fn (Builder $query) => $query->whereDate($column, '>=', $request->date('start_date')))
            ->when($request->filled('end_date'), fn (Builder $query) => $query->whereDate($column, '<=', $request->date('end_date')));
    }

    private function periodLabel(Request $request): string
    {
        if ($request->filled('start_date') || $request->filled('end_date')) {
            return 'Periode: '.($request->start_date ?: 'awal').' s/d '.($request->end_date ?: 'akhir');
        }

        return 'Periode: semua data';
    }

    private function filename(string $prefix): string
    {
        return $prefix.'-'.now()->format('Ymd-His').'.pdf';
    }

    private function dateValue(mixed $value): string
    {
        if ($value instanceof CarbonInterface) {
            return $value->format('d/m/Y');
        }

        return filled($value) ? (string) $value : '-';
    }

    private function number(mixed $value, int $decimals = 2, string $suffix = ''): string
    {
        if ($value === null || $value === '') {
            return '-';
        }

        return number_format((float) $value, $decimals, ',', '.').$suffix;
    }
}
