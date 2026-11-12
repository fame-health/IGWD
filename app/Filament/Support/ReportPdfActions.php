<?php

namespace App\Filament\Support;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Support\Icons\Heroicon;

class ReportPdfActions
{
    public static function download(string $report, string $label = 'Unduh PDF'): Action
    {
        return Action::make('download_'.str_replace('-', '_', $report).'_pdf')
            ->label($label)
            ->icon(Heroicon::OutlinedArrowDownTray)
            ->color('success')
            ->url(fn (): string => route('filament.reports.pdf', ['report' => $report]));
    }

    public static function dashboardGroup(): ActionGroup
    {
        return ActionGroup::make([
            self::download('all-reports', 'Semua Laporan'),
            self::download('dashboard', 'Ringkasan Dashboard'),
            self::download('daily-monitoring', 'Monitoring Harian'),
            self::download('dialysis-sessions', 'Sesi HD'),
            self::download('patient-biodata', 'Seluruh Biodata Pasien'),
            self::download('risk-patients', 'Pasien Risiko'),
            self::download('risk-alerts', 'Notifikasi Risiko'),
        ])
            ->label('Unduh Laporan PDF')
            ->icon(Heroicon::OutlinedDocumentArrowDown)
            ->color('success')
            ->button();
    }
}
