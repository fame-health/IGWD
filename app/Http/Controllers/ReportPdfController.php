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
            'all-reports' => $this->allReports($request),
            'dashboard' => $this->dashboardReport(),
            'daily-monitoring' => $this->dailyMonitoringReport($request),
            'dialysis-sessions' => $this->dialysisSessionReport($request),
            'patient-biodata' => $this->patientBiodataReport(),
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
    private function allReports(Request $request): array
    {
        $dashboard = $this->dashboardReport();
        $dailyMonitoring = $this->dailyMonitoringReport($request);
        $dialysisSessions = $this->dialysisSessionReport($request);
        $patientBiodata = $this->patientBiodataReport();
        $riskPatients = $this->riskPatientReport($request);
        $riskAlerts = $this->riskAlertReport($request);

        return [
            'type' => 'all-combined',
            'title' => 'Laporan Lengkap IDWG',
            'subtitle' => $this->periodLabel($request).' | Semua laporan dalam satu file',
            'filename' => $this->filename('laporan-lengkap-idwg'),
            'summary' => [
                'Total Pasien' => $dashboard['summary']['Total Pasien'],
                'Pasien Aktif' => $dashboard['summary']['Pasien Aktif'],
                'Monitoring' => $dailyMonitoring['summary']['Total Monitoring'],
                'Sesi HD' => $dialysisSessions['summary']['Total Sesi'],
                'Pasien Risiko' => $riskPatients['summary']['Total Pasien Risiko'],
                'Notifikasi Risiko' => $riskAlerts['summary']['Total Alert'],
            ],
            'sections' => [
                ['is_big_title' => true, 'title' => 'RINGKASAN DASHBOARD'],
                $this->tableSectionFromReport($dashboard, 'Ringkasan Dashboard'),

                ['is_big_title' => true, 'title' => 'BIODATA PASIEN'],
                ...$this->biodataSections($patientBiodata),

                ['is_big_title' => true, 'title' => 'MONITORING HARIAN'],
                $this->tableSectionFromReport($dailyMonitoring, 'Monitoring Harian'),

                ['is_big_title' => true, 'title' => 'SESI HEMODIALISIS (HD)'],
                $this->tableSectionFromReport($dialysisSessions, 'Sesi HD (Per Pasien)'),

                ['is_big_title' => true, 'title' => 'LAPORAN PASIEN RISIKO'],
                $this->tableSectionFromReport($riskPatients, 'Pasien Risiko'),

                ['is_big_title' => true, 'title' => 'NOTIFIKASI RISIKO'],
                $this->tableSectionFromReport($riskAlerts, 'Notifikasi Risiko'),
            ],
        ];
    }

    private function biodataSections(array $patientBiodata): array
    {
        // Based on your current implementation, if it's grid-tables,
        // we might need to handle it differently, but for All Reports
        // we usually flatten or keep sections.
        return $patientBiodata['sections'] ?? [];
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
        $query = DialysisSession::query()->with(['patient', 'patient.medicalProfile']);
        $this->applyDateFilters($query, $request, 'session_date');

        // Get and sort data: Patient name ASC, then Date ASC (to count session numbers correctly)
        $data = $query->get()->sortBy([
            ['patient.name', 'asc'],
            ['session_date', 'asc'],
        ]);

        $rows = [];
        $currentPatientId = null;
        $sessionCount = 0;

        foreach ($data as $record) {
            if ($currentPatientId !== $record->patient_id) {
                $patient = $record->patient;
                $rows[] = [
                    'is_header' => true,
                    'content' => sprintf(
                        'PASIEN: %s | RM: %s | NIK: %s | Goldar: %s',
                        $patient?->name ?? '-',
                        $patient?->medical_record_number ?? '-',
                        $patient?->nik ?? '-',
                        $patient?->medicalProfile?->blood_type ?? '-'
                    ),
                ];
                $currentPatientId = $record->patient_id;
                $sessionCount = 0;
            }

            $sessionCount++;

            // Enhanced fallback for dry weight:
            // 1. Use session's dry_weight if exists and > 0
            // 2. Use patient's medical profile dry_weight if exists and > 0
            // 3. Fallback to current_pre_hd_weight as last resort
            $dwValue = (float)($record->dry_weight ?? 0);
            if ($dwValue <= 0) {
                $dwValue = (float)($record->patient?->medicalProfile?->dry_weight ?? 0);
            }
            if ($dwValue <= 0) {
                $dwValue = (float)($record->current_pre_hd_weight ?? 0);
            }

            // If still 0, use null so $this->number() returns '-'
            $dryWeight = ($dwValue > 0) ? $dwValue : null;

            $rows[] = [
                'SESI ' . $sessionCount,
                $this->dateValue($record->session_date),
                $record->shift ?? '-',
                $this->number($record->current_pre_hd_weight, 2, ' kg'),
                $this->number($dryWeight, 2, ' kg'), // Using the fallback dry weight
                $this->number($record->idwg_kg, 2, ' kg'),
                $this->number($record->idwg_percent, 2, ' %'),
                $record->risk_category ?? '-',
                $this->number($record->target_ultrafiltration, 2),
                $this->number($record->hd_duration_minutes, 0, ' mnt'),
            ];
        }

        return [
            'title' => 'Laporan Sesi HD (Per Pasien)',
            'subtitle' => $this->periodLabel($request),
            'filename' => $this->filename('laporan-sesi-hd'),
            'summary' => [
                'Total Sesi' => $data->count(),
                'Rata-rata IDWG' => $this->number($data->avg('idwg_percent'), 2, ' %'),
                'Risiko Tinggi/Darurat' => $data->whereIn('risk_category', ['Tinggi', 'Darurat'])->count(),
                'Total Target UF' => $this->number($data->sum('target_ultrafiltration'), 2),
            ],
            'columns' => [
                ['label' => 'Sesi Ke', 'width' => 0.08],
                ['label' => 'Tanggal', 'width' => 0.10],
                ['label' => 'Shift', 'width' => 0.06],
                ['label' => 'BB Pre', 'width' => 0.10],
                ['label' => 'BB Kering', 'width' => 0.10],
                ['label' => 'IDWG kg', 'width' => 0.10],
                ['label' => 'IDWG %', 'width' => 0.10],
                ['label' => 'Risiko', 'width' => 0.11],
                ['label' => 'UF', 'width' => 0.10],
                ['label' => 'Durasi', 'width' => 0.15],
            ],
            'rows' => $rows,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function patientBiodataReport(): array
    {
        $patients = Patient::query()
            ->withTrashed()
            ->with(['medicalProfile', 'latestDialysisSession'])
            ->orderBy('name')
            ->get();

        $sections = [];

        foreach ($patients as $patient) {
            // Priority for Dry Weight:
            // 1. Latest HD Session dry_weight
            // 2. Medical Profile dry_weight
            // 3. Latest HD Session pre-weight (fallback)
            $dwValue = (float)($patient->latestDialysisSession?->dry_weight ?? 0);
            if ($dwValue <= 0) {
                $dwValue = (float)($patient->medicalProfile?->dry_weight ?? 0);
            }
            if ($dwValue <= 0) {
                $dwValue = (float)($patient->latestDialysisSession?->current_pre_hd_weight ?? 0);
            }

            // If still 0, use null so $this->number() returns '-' instead of '0,0 kg'
            $dryWeight = ($dwValue > 0) ? $dwValue : null;

            $sections[] = [
                'title' => 'Biodata Pasien: '.$patient->name,
                'subtitle' => 'No. RM: '.($patient->medical_record_number ?? '-'),
                'columns' => [
                    ['label' => 'Kategori', 'width' => 0.28],
                    ['label' => 'Informasi Detail', 'width' => 0.72],
                ],
                'rows' => [
                    ['Nama Pasien', $patient->name ?? '-'],
                    ['Identitas', 'NIK: '.($patient->nik ?? '-').' | Lahir: '.$this->dateValue($patient->birth_date).' ('.$this->ageValue($patient).') | JK: '.$this->genderValue($patient->gender)],
                    ['Kontak/Alamat', 'Telp: '.($patient->phone ?? '-').' | Alamat: '.($patient->address ?? '-')],
                    ['Diagnosa HD', 'Utama: '.($patient->medicalProfile?->main_diagnosis ?? '-').' | Sejak: '.$this->dateValue($patient->medicalProfile?->hemodialysis_start_date)],
                    ['Riwayat Penyakit', ($patient->medicalProfile?->medical_history ?? '-')],
                    ['Parameter HD', 'Goldar: '.($patient->medicalProfile?->blood_type ?? '-').' | Frek: '.($patient->medicalProfile?->hemodialysis_frequency ?? '-').' | BB Kering: '.$this->number($dryWeight, 1, ' kg')],
                    ['Akses & Bayar', 'Akses: '.($patient->medicalProfile?->vascular_access ?? '-').' | Bayar: '.($patient->payment_status ?? '-')],
                ],
                'max_lines_per_cell' => 4,
            ];
        }

        return [
            'type' => 'grid-tables',
            'title' => 'Laporan Ringkasan Biodata Pasien',
            'subtitle' => 'Format tabel ringkas (6 pasien per halaman) dengan riwayat medis',
            'filename' => $this->filename('laporan-biodata-lengkap'),
            'summary' => [
                'Total Pasien' => $patients->count(),
                'Pasien Aktif' => $patients->where('patient_status', 'Aktif')->count(),
                'Profil Medis' => $patients->whereNotNull('medicalProfile')->count(),
                'Pembayaran BPJS' => $patients->where('payment_status', 'BPJS')->count(),
            ],
            'sections' => $sections,
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
     * @param  array<string, mixed>  $report
     * @return array<string, mixed>
     */
    private function tableSectionFromReport(array $report, ?string $title = null): array
    {
        return [
            'title' => $title ?? $report['title'],
            'subtitle' => $report['subtitle'],
            'columns' => $report['columns'],
            'rows' => $report['rows'],
            'max_lines_per_cell' => $report['max_lines_per_cell'] ?? 3,
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

        if (($definition['type'] ?? '') === 'grid-tables') {
            $this->drawGridTables($pdf, $definition, $y - 10, $pageNumber);
        } elseif (isset($definition['sections'])) {
            $this->drawSections($pdf, $definition, $y - 10, $pageNumber);
        } else {
            $this->drawTable($pdf, $definition, $y - 10, $pageNumber);
        }

        return $pdf->render();
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    private function drawHeader(SimplePdfDocument $pdf, array $definition, int $pageNumber, ?string $currentSection = null): float
    {
        $margin = 32;

        // Use currentSection title if provided (for combined reports)
        $mainTitle = $currentSection ?: $definition['title'];
        $displayTitle = $mainTitle;

        // If it's a continuation page, show "(lanjutan)"
        if ($pageNumber > 1) {
            $displayTitle .= ' (lanjutan)';
        }

        $pdf->text($margin, $pdf->height() - 32, $displayTitle, 16, true);
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
    private function drawGridTables(SimplePdfDocument $pdf, array $definition, float $y, int &$pageNumber): void
    {
        $margin = 25; // Reduced margin from 32
        $gapX = 10; // Reduced gap from 15
        $gapY = 5;  // Reduced gap from 12
        $cols = 2;
        $width = ($pdf->width() - ($margin * 2) - $gapX) / $cols;
        $sections = $definition['sections'];
        $cardHeight = 160; // Increased height to fill page more

        foreach ($sections as $index => $section) {
            $col = $index % $cols;
            $rowInPage = intdiv($index, $cols) % 3;

            if ($index > 0 && $index % 6 === 0) {
                $pdf->addPage();
                $pageNumber++;
                $y = $this->drawHeader($pdf, $definition, $pageNumber);
                $y -= 8;
            }

            $x = $margin + ($col * ($width + $gapX));
            $currentY = $y - ($rowInPage * ($cardHeight + $gapY));

            // Section Header (Patient Name)
            $pdf->fillRect($x, $currentY - 18, $width, 18, [0.92, 0.96, 0.96]);
            $pdf->strokeRect($x, $currentY - 18, $width, 18, [0.74, 0.82, 0.82], 0.45);
            $pdf->text($x + 6, $currentY - 13, $section['title'], 8.5, true, [0.08, 0.18, 0.18]);

            $tableY = $currentY - 18;
            $rowHeight = 17; // Increased from 15

            // Draw Subtitle (RM) - Small and compact
            $pdf->text($x + 6, $tableY - 9, $section['subtitle'], 7, false, [0.4, 0.45, 0.5]);
            $tableY -= 14;

            // Simple Table Draw for Grid
            foreach ($section['rows'] as $row) {
                $pdf->fillRect($x, $tableY - $rowHeight, $width * 0.28, $rowHeight, [0.97, 0.98, 0.98]);
                $pdf->strokeRect($x, $tableY - $rowHeight, $width, $rowHeight, [0.85, 0.88, 0.88], 0.25);

                $pdf->text($x + 5, $tableY - 11, (string)$row[0], 7.5, true, [0.2, 0.2, 0.2]);

                $content = (string)$row[1];
                $wrapped = $pdf->wrapText($content, ($width * 0.72) - 8, 7.5);
                $pdf->text($x + ($width * 0.28) + 5, $tableY - 11, $wrapped[0] ?? '-', 7.2, false, [0.1, 0.1, 0.1]);

                $tableY -= $rowHeight;
            }
        }
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    private function drawGridCards(SimplePdfDocument $pdf, array $definition, float $y, int &$pageNumber): void
    {
        $margin = 32;
        $gap = 12;
        $cols = 2;
        $cardWidth = ($pdf->width() - ($margin * 2) - $gap) / $cols;
        $cardHeight = 180; // Estimated height for 4 cards per A4 Landscape

        $cards = $definition['cards'];

        foreach ($cards as $index => $card) {
            $col = $index % $cols;
            $rowInPage = intdiv($index, $cols) % 2; // 0 or 1 for 4 cards total

            if ($index > 0 && $index % 4 === 0) {
                $pdf->addPage();
                $pageNumber++;
                $y = $this->drawHeader($pdf, $definition, $pageNumber);
                $y -= 10;
            }

            $x = $margin + ($col * ($cardWidth + $gap));
            $currentY = $y - ($rowInPage * ($cardHeight + $gap));

            // Card background & border
            $pdf->fillRect($x, $currentY - $cardHeight, $cardWidth, $cardHeight, [0.97, 0.98, 0.99]);
            $pdf->strokeRect($x, $currentY - $cardHeight, $cardWidth, $cardHeight, [0.80, 0.85, 0.88], 0.6);

            // Card Header (Name & RM)
            $pdf->fillRect($x, $currentY - 24, $cardWidth, 24, [0.88, 0.92, 0.94]);
            $pdf->text($x + 8, $currentY - 16, $card['name'], 9, true, [0.08, 0.15, 0.25]);
            $pdf->text($x + $cardWidth - 80, $currentY - 16, 'RM: '.$card['rm'], 8, false, [0.3, 0.4, 0.5]);

            // Card Content
            $textY = $currentY - 38;
            foreach ($card['details'] as $label => $value) {
                $pdf->text($x + 10, $textY, $label.':', 7.5, true, [0.3, 0.35, 0.4]);
                $pdf->text($x + 55, $textY, (string) $value, 7.5, false, [0.1, 0.1, 0.1]);
                $textY -= 14;
            }
        }
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    private function drawSections(SimplePdfDocument $pdf, array $definition, float $y, int &$pageNumber): void
    {
        $currentSectionName = null;

        foreach ($definition['sections'] as $index => $section) {
            // Check for potential section titles in "all-reports"
            if (isset($section['is_big_title']) && $section['is_big_title']) {
                $currentSectionName = $section['title']; // Track section for headers
                $pdf->addPage();
                $pageNumber = 1; // Reset for "(lanjutan)" per section
                $this->drawBigSectionTitle($pdf, $section['title']);
                $pdf->addPage();
                $pageNumber = 1;
                $y = $this->drawHeader($pdf, $definition, $pageNumber, $currentSectionName);
                continue;
            }

            $y = $this->ensureSpace($pdf, $definition, $y - ($index === 0 ? 0 : 12), $pageNumber, 74, $currentSectionName);
            $y = $this->drawSectionHeader($pdf, $section, $y);

            $maxLinesPerCell = (int) ($section['max_lines_per_cell'] ?? $definition['max_lines_per_cell'] ?? 3);
            $y = $this->drawTable($pdf, [
                'columns' => $section['columns'],
                'rows' => $section['rows'],
                'title' => $definition['title'],
                'subtitle' => $definition['subtitle'],
                'max_lines_per_cell' => $maxLinesPerCell,
                'section_name' => $currentSectionName // Pass to drawTable
            ], $y - 8, $pageNumber);
        }
    }

    private function drawBigSectionTitle(SimplePdfDocument $pdf, string $title): void
    {
        $middleX = $pdf->width() / 2;
        $middleY = $pdf->height() / 2;

        // Background decoration
        $pdf->fillRect(0, $middleY - 40, $pdf->width(), 80, [0.95, 0.97, 0.98]);
        $pdf->line(0, $middleY - 40, $pdf->width(), $middleY - 40, [0.8, 0.85, 0.9], 1);
        $pdf->line(0, $middleY + 40, $pdf->width(), $middleY + 40, [0.8, 0.85, 0.9], 1);

        // Center text
        $approxWidth = strlen($title) * 12;
        $pdf->text($middleX - ($approxWidth / 2), $middleY - 8, strtoupper($title), 24, true, [0.08, 0.18, 0.3]);
        $pdf->text($middleX - 80, $middleY - 28, 'IGWD REPORT SYSTEM - BAGIAN LAPORAN', 8, false, [0.4, 0.5, 0.6]);
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    private function ensureSpace(SimplePdfDocument $pdf, array $definition, float $y, int &$pageNumber, float $requiredSpace, ?string $currentSection = null): float
    {
        if (($y - $requiredSpace) >= 32) {
            return $y;
        }

        $pdf->addPage();
        $pageNumber++;

        return $this->drawHeader($pdf, $definition, $pageNumber, $currentSection);
    }

    /**
     * @param  array<string, mixed>  $section
     */
    private function drawSectionHeader(SimplePdfDocument $pdf, array $section, float $y): float
    {
        $margin = 32;
        $height = filled($section['subtitle'] ?? null) ? 34 : 25;

        $pdf->fillRect($margin, $y - $height, $pdf->width() - ($margin * 2), $height, [0.92, 0.96, 0.96]);
        $pdf->strokeRect($margin, $y - $height, $pdf->width() - ($margin * 2), $height, [0.74, 0.82, 0.82], 0.45);
        $pdf->text($margin + 10, $y - 16, (string) $section['title'], 10.5, true, [0.08, 0.18, 0.18]);

        if (filled($section['subtitle'] ?? null)) {
            $pdf->text($margin + 10, $y - 29, (string) $section['subtitle'], 7.4, false, [0.35, 0.39, 0.45]);
        }

        return $y - $height;
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    private function drawTable(SimplePdfDocument $pdf, array $definition, float $y, int &$pageNumber): float
    {
        $rows = $definition['rows'];
        $sectionName = $definition['section_name'] ?? null;

        if ($rows === []) {
            $pdf->text(32, $y, 'Tidak ada data laporan.', 9.5, false, [0.35, 0.39, 0.45]);

            return $y - 18;
        }

        $columns = $definition['columns'];
        $widths = $this->columnWidths($pdf, $columns);
        $y = $this->drawTableHeader($pdf, $columns, $widths, $y);
        $maxLinesPerCell = (int) ($definition['max_lines_per_cell'] ?? 3);

        foreach ($rows as $rowIndex => $row) {
            if (isset($row['is_header'])) {
                $y = $this->drawTableSectionHeader($pdf, $row['content'], $y, $pageNumber, $definition);
                continue;
            }

            $wrappedCells = [];
            $maxLines = 1;

            foreach (array_values($row) as $cellIndex => $cell) {
                $lines = $this->limitLines($pdf->wrapText((string) $cell, $widths[$cellIndex] - 8, 7.2), $maxLinesPerCell);
                $wrappedCells[] = $lines;
                $maxLines = max($maxLines, count($lines));
            }

            $rowHeight = max(20, ($maxLines * 9) + 8);

            if (($y - $rowHeight) < 32) {
                $pdf->addPage();
                $pageNumber++;
                $y = $this->drawHeader($pdf, $definition, $pageNumber, $sectionName);
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

        return $y;
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    private function drawTableSectionHeader(SimplePdfDocument $pdf, string $content, float $y, int &$pageNumber, array $definition): float
    {
        $height = 20;

        if (($y - $height) < 32) {
            $pdf->addPage();
            $pageNumber++;
            $y = $this->drawHeader($pdf, $definition, $pageNumber);
            $y = $this->drawTableHeader($pdf, $definition['columns'], $this->columnWidths($pdf, $definition['columns']), $y);
        }

        $pdf->fillRect(32, $y - $height, $pdf->width() - 64, $height, [0.90, 0.93, 0.95]);
        $pdf->strokeRect(32, $y - $height, $pdf->width() - 64, $height, [0.70, 0.75, 0.80], 0.45);
        $pdf->text(38, $y - 13, $content, 8, true, [0.10, 0.20, 0.30]);

        return $y - $height;
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
    private function limitLines(array $lines, int $maxLines = 3): array
    {
        $maxLines = max(1, $maxLines);

        if (count($lines) <= $maxLines) {
            return $lines;
        }

        $limited = array_slice($lines, 0, $maxLines);
        $lastIndex = $maxLines - 1;
        $limited[$lastIndex] = rtrim(substr($limited[$lastIndex], 0, 34)).'...';

        return $limited;
    }

    private function hdSectionFromRoom(DialysisSession $record): ?string
    {
        $room = $record->schedule?->room;

        if (blank($room)) {
            return null;
        }

        $normalized = strtolower(preg_replace('/[^a-z0-9]+/i', '', (string) $room) ?? '');

        return match (true) {
            str_contains($normalized, 'hd1') => 'Sesi HD 1',
            str_contains($normalized, 'hd2') => 'Sesi HD 2',
            default => null,
        };
    }

    private function locationValue(DialysisSession $record): string
    {
        $parts = array_filter([
            $record->schedule?->room ? 'Ruang '.$record->schedule->room : null,
            $record->schedule?->machine_number ? 'Mesin '.$record->schedule->machine_number : null,
        ]);

        return $parts ? implode(' / ', $parts) : '-';
    }

    private function ufDurationValue(DialysisSession $record): string
    {
        $targetUf = $this->number($record->target_ultrafiltration, 2, ' L');
        $duration = $this->number($record->hd_duration_minutes, 0, ' menit');

        if ($targetUf === '-' && $duration === '-') {
            return '-';
        }

        return $targetUf.' / '.$duration;
    }

    private function ageValue(Patient $patient): string
    {
        if (! $patient->birth_date instanceof CarbonInterface) {
            return '-';
        }

        return $patient->birth_date->age.' tahun';
    }

    private function genderValue(?string $gender): string
    {
        return match ($gender) {
            'laki-laki' => 'Laki-laki',
            'perempuan' => 'Perempuan',
            default => filled($gender) ? (string) $gender : '-',
        };
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
