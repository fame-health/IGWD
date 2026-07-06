<?php

namespace App\Filament\Resources\DialysisSchedules\Widgets;

use App\Models\DialysisSchedule;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;

class DialysisScheduleOperationsBoard extends Widget
{
    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 0;

    protected string $view = 'filament.resources.dialysis-schedules.widgets.operations-board';

    protected ?string $pollingInterval = null;

    protected function getViewData(): array
    {
        $today = today();
        $nextWeek = $today->copy()->addDays(7);

        $todaySchedules = DialysisSchedule::query()
            ->select(['id', 'shift', 'attendance_status', 'room', 'machine_number', 'doctor_name', 'nurse_name'])
            ->whereDate('hd_date', $today)
            ->get();

        $totalToday = $todaySchedules->count();
        $attendedToday = $todaySchedules->where('attendance_status', 'Hadir')->count();
        $followUpToday = $todaySchedules
            ->whereIn('attendance_status', ['Tidak Hadir', 'Reschedule'])
            ->count();
        $incompleteTeamToday = $todaySchedules
            ->filter(fn (DialysisSchedule $schedule): bool => blank($schedule->doctor_name) || blank($schedule->nurse_name))
            ->count();

        $roomsUsed = $todaySchedules
            ->pluck('room')
            ->filter(fn (?string $room): bool => filled($room))
            ->unique()
            ->count();
        $machinesUsed = $todaySchedules
            ->pluck('machine_number')
            ->filter(fn (?string $machine): bool => filled($machine))
            ->unique()
            ->count();

        $statusCounts = $todaySchedules->countBy('attendance_status');
        $shiftCounts = $todaySchedules->countBy('shift');

        $shifts = $this->shiftSummary($shiftCounts);
        $maxShiftCount = max(1, $shifts->max('total') ?? 0);

        return [
            'attendanceRate' => $totalToday > 0 ? (int) round(($attendedToday / $totalToday) * 100) : 0,
            'dateLabel' => $today->translatedFormat('l, d M Y'),
            'lastUpdated' => now()->format('H:i'),
            'metrics' => [
                [
                    'label' => 'Kehadiran Hari Ini',
                    'value' => $attendedToday,
                    'unit' => "/ {$totalToday}",
                    'helper' => $totalToday > 0 ? round(($attendedToday / $totalToday) * 100).'% sudah hadir' : 'Belum ada jadwal',
                    'icon' => 'heroicon-o-user-group',
                    'tone' => $attendedToday === $totalToday && $totalToday > 0 ? 'success' : 'info',
                ],
                [
                    'label' => 'Perlu Follow Up',
                    'value' => $followUpToday,
                    'unit' => 'jadwal',
                    'helper' => 'Tidak hadir atau reschedule',
                    'icon' => $followUpToday > 0 ? 'heroicon-o-bell-alert' : 'heroicon-o-check-circle',
                    'tone' => $followUpToday > 0 ? 'warning' : 'success',
                ],
                [
                    'label' => 'Ruang & Mesin',
                    'value' => $roomsUsed,
                    'unit' => 'ruang',
                    'helper' => "{$machinesUsed} mesin digunakan hari ini",
                    'icon' => 'heroicon-o-map-pin',
                    'tone' => 'info',
                ],
                [
                    'label' => 'Kelengkapan Tim',
                    'value' => max(0, $totalToday - $incompleteTeamToday),
                    'unit' => "/ {$totalToday}",
                    'helper' => $incompleteTeamToday > 0 ? "{$incompleteTeamToday} jadwal belum lengkap" : 'Dokter dan perawat lengkap',
                    'icon' => $incompleteTeamToday > 0 ? 'heroicon-o-exclamation-triangle' : 'heroicon-o-check-badge',
                    'tone' => $incompleteTeamToday > 0 ? 'warning' : 'success',
                ],
            ],
            'nextSchedules' => DialysisSchedule::query()
                ->select(['id', 'patient_id', 'hd_date', 'day_name', 'shift', 'room', 'machine_number', 'doctor_name', 'attendance_status'])
                ->with(['patient:id,name'])
                ->whereBetween('hd_date', [$today->toDateString(), $nextWeek->toDateString()])
                ->orderBy('hd_date')
                ->orderByRaw("case shift when 'Pagi' then 1 when 'Siang' then 2 when 'Sore' then 3 when 'Malam' then 4 else 5 end")
                ->limit(5)
                ->get(),
            'shiftMax' => $maxShiftCount,
            'shifts' => $shifts,
            'statuses' => $this->statusSummary($statusCounts),
            'totalToday' => $totalToday,
        ];
    }

    private function shiftSummary(Collection $shiftCounts): Collection
    {
        return collect([
            'Pagi' => ['icon' => 'heroicon-o-sun', 'tone' => 'sky'],
            'Siang' => ['icon' => 'heroicon-o-clock', 'tone' => 'amber'],
            'Sore' => ['icon' => 'heroicon-o-cloud', 'tone' => 'emerald'],
            'Malam' => ['icon' => 'heroicon-o-moon', 'tone' => 'slate'],
        ])->map(fn (array $config, string $shift): array => [
            ...$config,
            'label' => $shift,
            'total' => (int) ($shiftCounts[$shift] ?? 0),
        ])->values();
    }

    private function statusSummary(Collection $statusCounts): Collection
    {
        return collect([
            'Terjadwal' => ['icon' => 'heroicon-o-clock', 'tone' => 'amber'],
            'Hadir' => ['icon' => 'heroicon-o-check-circle', 'tone' => 'emerald'],
            'Tidak Hadir' => ['icon' => 'heroicon-o-x-circle', 'tone' => 'rose'],
            'Reschedule' => ['icon' => 'heroicon-o-arrow-path', 'tone' => 'sky'],
        ])->map(fn (array $config, string $status): array => [
            ...$config,
            'label' => $status,
            'total' => (int) ($statusCounts[$status] ?? 0),
        ])->values();
    }
}
