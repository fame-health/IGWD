<?php

namespace App\Filament\Resources\DialysisSchedules\Widgets;

use App\Models\DialysisSchedule;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DialysisScheduleStats extends StatsOverviewWidget
{
    protected ?string $pollingInterval = '60s';

    protected int|array|null $columns = [
        'md' => 2,
        'xl' => 4,
    ];

    protected function getStats(): array
    {
        $today = today();
        $nextWeek = today()->addDays(7);

        $todaySchedules = DialysisSchedule::whereDate('hd_date', $today)->count();
        $attendedToday = DialysisSchedule::whereDate('hd_date', $today)
            ->where('attendance_status', 'Hadir')
            ->count();
        $needsFollowUp = DialysisSchedule::whereDate('hd_date', $today)
            ->whereIn('attendance_status', ['Tidak Hadir', 'Reschedule'])
            ->count();
        $upcomingSchedules = DialysisSchedule::whereDate('hd_date', '>', $today)
            ->whereDate('hd_date', '<=', $nextWeek)
            ->count();

        return [
            Stat::make('Jadwal Hari Ini', number_format($todaySchedules))
                ->description('Total pasien terjadwal')
                ->descriptionIcon(Heroicon::OutlinedCalendarDays)
                ->icon(Heroicon::OutlinedClock)
                ->color('info'),
            Stat::make('Sudah Hadir', number_format($attendedToday))
                ->description($todaySchedules > 0 ? round(($attendedToday / $todaySchedules) * 100) . '% dari jadwal hari ini' : 'Belum ada jadwal hari ini')
                ->descriptionIcon(Heroicon::OutlinedCheckCircle)
                ->icon(Heroicon::OutlinedUserGroup)
                ->color('success'),
            Stat::make('Perlu Follow Up', number_format($needsFollowUp))
                ->description('Tidak hadir atau reschedule')
                ->descriptionIcon($needsFollowUp > 0 ? Heroicon::OutlinedExclamationTriangle : Heroicon::OutlinedCheckCircle)
                ->icon(Heroicon::OutlinedBellAlert)
                ->color($needsFollowUp > 0 ? 'warning' : 'success'),
            Stat::make('7 Hari Ke Depan', number_format($upcomingSchedules))
                ->description('Jadwal setelah hari ini')
                ->descriptionIcon(Heroicon::OutlinedArrowTrendingUp)
                ->icon(Heroicon::OutlinedCalendar)
                ->color('gray'),
        ];
    }
}
