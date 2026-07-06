@php
    $metricTones = [
        'success' => [
            'panel' => 'border-emerald-200 bg-emerald-50/80 dark:border-emerald-400/20 dark:bg-emerald-950/20',
            'icon' => 'bg-emerald-600 text-white',
            'label' => 'text-emerald-700 dark:text-emerald-300',
            'value' => 'text-emerald-950 dark:text-emerald-50',
        ],
        'info' => [
            'panel' => 'border-sky-200 bg-sky-50/80 dark:border-sky-400/20 dark:bg-sky-950/20',
            'icon' => 'bg-sky-600 text-white',
            'label' => 'text-sky-700 dark:text-sky-300',
            'value' => 'text-sky-950 dark:text-sky-50',
        ],
        'warning' => [
            'panel' => 'border-amber-200 bg-amber-50/80 dark:border-amber-400/20 dark:bg-amber-950/20',
            'icon' => 'bg-amber-500 text-white',
            'label' => 'text-amber-700 dark:text-amber-300',
            'value' => 'text-amber-950 dark:text-amber-50',
        ],
    ];

    $itemTones = [
        'sky' => 'bg-sky-50 text-sky-700 ring-sky-200 dark:bg-sky-400/10 dark:text-sky-300 dark:ring-sky-400/20',
        'amber' => 'bg-amber-50 text-amber-700 ring-amber-200 dark:bg-amber-400/10 dark:text-amber-300 dark:ring-amber-400/20',
        'emerald' => 'bg-emerald-50 text-emerald-700 ring-emerald-200 dark:bg-emerald-400/10 dark:text-emerald-300 dark:ring-emerald-400/20',
        'slate' => 'bg-slate-50 text-slate-700 ring-slate-200 dark:bg-slate-800 dark:text-slate-200 dark:ring-white/10',
        'rose' => 'bg-rose-50 text-rose-700 ring-rose-200 dark:bg-rose-400/10 dark:text-rose-300 dark:ring-rose-400/20',
    ];
@endphp

<x-filament-widgets::widget>
    <section class="dialysis-schedule-board rounded-lg border border-slate-200 bg-white/95 shadow-sm dark:border-white/10 dark:bg-slate-900/95">
        <div class="grid gap-3 p-3 xl:grid-cols-[minmax(0,1fr)_18rem]">
            <div class="min-w-0 space-y-3">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex min-w-0 items-center gap-3">
                        <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-teal-600 text-white">
                            <x-filament::icon icon="heroicon-o-calendar-days" class="h-5 w-5" />
                        </span>
                        <div class="min-w-0">
                            <p class="text-xs font-semibold uppercase text-teal-700 dark:text-teal-300">
                                Operasional HD
                            </p>
                            <h2 class="truncate text-base font-semibold text-slate-950 dark:text-white">
                                {{ $dateLabel }}
                            </h2>
                        </div>
                    </div>

                    <div class="grid gap-2 sm:grid-cols-[10rem_1fr] lg:min-w-[22rem]">
                        <div class="rounded-md bg-slate-50 px-2.5 py-2 ring-1 ring-slate-200 dark:bg-slate-800/70 dark:ring-white/10">
                            <div class="flex items-center justify-between gap-2 text-xs font-semibold text-slate-500 dark:text-slate-400">
                                <span>Kehadiran</span>
                                <span class="text-slate-950 dark:text-white">{{ $attendanceRate }}%</span>
                            </div>
                            <div class="mt-1.5 h-1.5 overflow-hidden rounded-full bg-slate-200 dark:bg-slate-700">
                                <div class="h-full rounded-full bg-teal-600" style="width: {{ $attendanceRate }}%"></div>
                            </div>
                        </div>

                        <div class="flex items-center justify-between gap-2 rounded-md bg-slate-50 px-2.5 py-2 text-xs text-slate-500 ring-1 ring-slate-200 dark:bg-slate-800/70 dark:text-slate-400 dark:ring-white/10">
                            <span>Total hari ini</span>
                            <span class="font-semibold text-slate-950 dark:text-white">{{ $totalToday }} jadwal</span>
                        </div>
                    </div>
                </div>

                <div class="grid gap-2 sm:grid-cols-2 xl:grid-cols-4">
                    @foreach ($metrics as $metric)
                        <div class="{{ $metricTones[$metric['tone']]['panel'] ?? $metricTones['info']['panel'] }} rounded-lg border px-3 py-2">
                            <div class="flex items-center justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="{{ $metricTones[$metric['tone']]['label'] ?? $metricTones['info']['label'] }} truncate text-xs font-semibold">
                                        {{ $metric['label'] }}
                                    </p>
                                    <div class="mt-1 flex items-end gap-1.5">
                                        <p class="{{ $metricTones[$metric['tone']]['value'] ?? $metricTones['info']['value'] }} text-xl font-semibold leading-none">
                                            {{ number_format($metric['value']) }}
                                        </p>
                                        <p class="{{ $metricTones[$metric['tone']]['label'] ?? $metricTones['info']['label'] }} text-xs font-medium">
                                            {{ $metric['unit'] }}
                                        </p>
                                    </div>
                                </div>
                                <span class="{{ $metricTones[$metric['tone']]['icon'] ?? $metricTones['info']['icon'] }} grid h-8 w-8 shrink-0 place-items-center rounded-md">
                                    <x-filament::icon :icon="$metric['icon']" class="h-4 w-4" />
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="grid gap-2 lg:grid-cols-2">
                    <div class="rounded-lg border border-slate-200 bg-white p-3 dark:border-white/10 dark:bg-slate-900">
                        <div class="mb-2 flex items-center justify-between gap-3">
                            <h3 class="text-sm font-semibold text-slate-950 dark:text-white">Shift</h3>
                            <span class="text-xs text-slate-500 dark:text-slate-400">Diperbarui {{ $lastUpdated }} WIB</span>
                        </div>

                        <div class="grid gap-2 sm:grid-cols-2">
                            @foreach ($shifts as $shift)
                                <div class="grid grid-cols-[5.5rem_minmax(0,1fr)_1.5rem] items-center gap-2">
                                    <span class="{{ $itemTones[$shift['tone'] ?? 'sky'] ?? $itemTones['sky'] }} inline-flex items-center gap-1 rounded-md px-2 py-1 text-xs font-semibold ring-1">
                                        <x-filament::icon :icon="$shift['icon']" class="h-3.5 w-3.5" />
                                        {{ $shift['label'] }}
                                    </span>
                                    <div class="h-1.5 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
                                        <div
                                            class="h-full rounded-full bg-teal-600"
                                            style="width: {{ $shift['total'] > 0 ? max(8, (int) round(($shift['total'] / $shiftMax) * 100)) : 0 }}%"
                                        ></div>
                                    </div>
                                    <span class="text-right text-sm font-semibold text-slate-950 dark:text-white">
                                        {{ $shift['total'] }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="rounded-lg border border-slate-200 bg-white p-3 dark:border-white/10 dark:bg-slate-900">
                        <h3 class="mb-2 text-sm font-semibold text-slate-950 dark:text-white">Status</h3>

                        <div class="grid gap-2 sm:grid-cols-2">
                            @foreach ($statuses as $status)
                                <div class="{{ $itemTones[$status['tone'] ?? 'sky'] ?? $itemTones['sky'] }} flex items-center justify-between gap-2 rounded-md px-2.5 py-1.5 ring-1">
                                    <span class="flex min-w-0 items-center gap-1.5 text-xs font-semibold">
                                        <x-filament::icon :icon="$status['icon']" class="h-3.5 w-3.5 shrink-0" />
                                        <span class="truncate">{{ $status['label'] }}</span>
                                    </span>
                                    <span class="text-sm font-semibold">{{ $status['total'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <aside class="rounded-lg border border-slate-200 bg-slate-50 p-3 dark:border-white/10 dark:bg-slate-800/60">
                <div class="mb-2 flex items-center justify-between gap-3">
                    <div class="min-w-0">
                        <h3 class="truncate text-sm font-semibold text-slate-950 dark:text-white">
                            Jadwal Terdekat
                        </h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400">
                            3 jadwal dalam 7 hari
                        </p>
                    </div>
                    <span class="grid h-8 w-8 shrink-0 place-items-center rounded-lg bg-white text-teal-700 dark:bg-slate-900 dark:text-teal-300">
                        <x-filament::icon icon="heroicon-o-arrow-up-right" class="h-4 w-4" />
                    </span>
                </div>

                <div class="space-y-2">
                    @forelse ($nextSchedules->take(3) as $schedule)
                        <div class="rounded-md bg-white px-2.5 py-2 ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-white/10">
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-slate-950 dark:text-white">
                                        {{ $schedule->patient?->name ?? 'Pasien tidak ditemukan' }}
                                    </p>
                                    <p class="mt-0.5 truncate text-xs text-slate-500 dark:text-slate-400">
                                        {{ $schedule->hd_date?->translatedFormat('d M') }} - {{ $schedule->shift ?? 'Shift kosong' }}
                                    </p>
                                </div>
                                <span class="shrink-0 rounded-md bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                                    {{ $schedule->hd_date?->isToday() ? 'Hari ini' : ($schedule->day_name ?: '-') }}
                                </span>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-md border border-dashed border-slate-300 bg-white p-4 text-center dark:border-white/15 dark:bg-slate-900">
                            <x-filament::icon icon="heroicon-o-calendar" class="mx-auto h-6 w-6 text-slate-400" />
                            <p class="mt-2 text-sm font-semibold text-slate-800 dark:text-slate-100">
                                Tidak ada jadwal dekat
                            </p>
                        </div>
                    @endforelse
                </div>
            </aside>
        </div>
    </section>
</x-filament-widgets::widget>
