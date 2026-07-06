<?php

namespace App\Filament\Resources\DialysisSchedules\Widgets;

use App\Models\DialysisSchedule;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class DialysisScheduleStats extends StatsOverviewWidget
{
    protected ?string $pollingInterval = null;

    protected int|array|null $columns = [
        'md' => 2,
        'xl' => 4,
    ];

    protected function getStats(): array
    {
        $today = today();
        $trendStart = $today->copy()->subDays(6);
        $trendEnd = $today->copy()->addDays(7);

        $rows = DialysisSchedule::query()
            ->selectRaw('DATE(hd_date) as schedule_date, attendance_status, count(*) as total')
            ->whereBetween('hd_date', [$trendStart->toDateString(), $trendEnd->toDateString()])
            ->groupBy('schedule_date', 'attendance_status')
            ->get();

        $todayDate = $today->toDateString();
        $todayRows = $rows->where('schedule_date', $todayDate);

        $todaySchedules = (int) $todayRows->sum('total');
        $attendedToday = (int) $todayRows
            ->where('attendance_status', 'Hadir')
            ->sum('total');
        $needsFollowUp = (int) $todayRows
            ->whereIn('attendance_status', ['Tidak Hadir', 'Reschedule'])
            ->sum('total');
        $upcomingSchedules = (int) $rows
            ->filter(fn (object $row): bool => $row->schedule_date > $todayDate)
            ->sum('total');

        return [
            Stat::make('Jadwal Hari Ini', number_format($todaySchedules))
                ->description('Total pasien terjadwal')
                ->descriptionIcon(Heroicon::OutlinedCalendarDays)
                ->icon(Heroicon::OutlinedClock)
                ->chart($this->scheduleTrend($rows, $trendStart))
                ->color('info'),
            Stat::make('Sudah Hadir', number_format($attendedToday))
                ->description($todaySchedules > 0 ? round(($attendedToday / $todaySchedules) * 100).'% dari jadwal hari ini' : 'Belum ada jadwal hari ini')
                ->descriptionIcon(Heroicon::OutlinedCheckCircle)
                ->icon(Heroicon::OutlinedUserGroup)
                ->chart($this->scheduleTrend($rows, $trendStart, ['Hadir']))
                ->color('success'),
            Stat::make('Perlu Follow Up', number_format($needsFollowUp))
                ->description('Tidak hadir atau reschedule')
                ->descriptionIcon($needsFollowUp > 0 ? Heroicon::OutlinedExclamationTriangle : Heroicon::OutlinedCheckCircle)
                ->icon(Heroicon::OutlinedBellAlert)
                ->chart($this->scheduleTrend($rows, $trendStart, ['Tidak Hadir', 'Reschedule']))
                ->color($needsFollowUp > 0 ? 'warning' : 'success'),
            Stat::make('7 Hari Ke Depan', number_format($upcomingSchedules))
                ->description('Jadwal setelah hari ini')
                ->descriptionIcon(Heroicon::OutlinedArrowTrendingUp)
                ->icon(Heroicon::OutlinedCalendar)
                ->chart($this->scheduleTrend($rows, $today->copy()->addDay()))
                ->color('gray'),
        ];
    }

    private function scheduleTrend(Collection $rows, Carbon $start, ?array $statuses = null): array
    {
        return collect(range(0, 6))
            ->map(function (int $offset) use ($rows, $start, $statuses): int {
                $date = $start->copy()->addDays($offset)->toDateString();

                return (int) $rows
                    ->where('schedule_date', $date)
                    ->when($statuses, fn (Collection $rows): Collection => $rows->whereIn('attendance_status', $statuses))
                    ->sum('total');
            })
            ->all();
    }
}
