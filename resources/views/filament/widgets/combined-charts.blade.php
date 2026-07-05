<x-filament-widgets::widget>
    <div class="space-y-3">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <h3 class="text-sm font-semibold text-slate-900 dark:text-white">
                Grafik Monitoring 14 Hari
            </h3>
            <span class="rounded-lg border border-teal-200 bg-teal-50 px-2.5 py-1 text-xs font-semibold text-teal-700 dark:border-teal-800 dark:bg-teal-950/30 dark:text-teal-300">
                Ringkas
            </span>
        </div>

        <div class="grid grid-cols-1 gap-3 lg:grid-cols-3">
            @foreach ($charts as $chart)
                @php
                    $colorClasses = [
                        'blue' => [
                            'bg' => 'bg-white dark:bg-slate-900',
                            'border' => 'border-sky-200 dark:border-sky-800',
                            'text' => 'text-sky-700 dark:text-sky-300',
                            'icon' => 'bg-sky-500 text-white',
                            'stroke' => 'text-sky-500',
                            'fill' => 'text-sky-100 dark:text-sky-950/50',
                        ],
                        'green' => [
                            'bg' => 'bg-white dark:bg-slate-900',
                            'border' => 'border-emerald-200 dark:border-emerald-800',
                            'text' => 'text-emerald-700 dark:text-emerald-300',
                            'icon' => 'bg-emerald-500 text-white',
                            'stroke' => 'text-emerald-500',
                            'fill' => 'text-emerald-100 dark:text-emerald-950/50',
                        ],
                        'red' => [
                            'bg' => 'bg-white dark:bg-slate-900',
                            'border' => 'border-rose-200 dark:border-rose-800',
                            'text' => 'text-rose-700 dark:text-rose-300',
                            'icon' => 'bg-rose-500 text-white',
                            'stroke' => 'text-rose-500',
                            'fill' => 'text-rose-100 dark:text-rose-950/50',
                        ],
                    ];
                    $colors = $colorClasses[$chart['color']];
                    $values = collect($chart['data']['values'])->map(fn ($value) => (float) $value)->values();
                    $labels = collect($chart['data']['labels'])->values();
                    $latest = $values->isNotEmpty() ? $values->last() : 0;
                    $avg = $values->isNotEmpty() ? round($values->avg(), 1) : 0;
                    $unit = $chart['unit'];

                    $width = 320;
                    $height = 112;
                    $paddingX = 10;
                    $paddingTop = 10;
                    $paddingBottom = 20;
                    $bottom = $height - $paddingBottom;
                    $plotHeight = $height - $paddingTop - $paddingBottom;
                    $count = $values->count();
                    $min = $count ? $values->min() : 0;
                    $max = $count ? $values->max() : 0;
                    $range = $max - $min;

                    $points = $values->map(function ($value, $index) use ($count, $width, $height, $paddingX, $paddingTop, $bottom, $plotHeight, $max, $min, $range) {
                        $x = $count > 1
                            ? $paddingX + ($index * (($width - ($paddingX * 2)) / ($count - 1)))
                            : $width / 2;

                        if ($range == 0.0) {
                            $y = $max == 0.0 ? $bottom : $height / 2;
                        } else {
                            $y = $paddingTop + (($max - $value) / $range) * $plotHeight;
                        }

                        return [
                            'x' => round($x, 2),
                            'y' => round($y, 2),
                        ];
                    });

                    if ($count === 1) {
                        $singleY = $points->first()['y'];
                        $linePoints = "{$paddingX},{$singleY} " . ($width - $paddingX) . ",{$singleY}";
                        $areaPath = "M {$paddingX} {$singleY} L " . ($width - $paddingX) . " {$singleY} L " . ($width - $paddingX) . " {$bottom} L {$paddingX} {$bottom} Z";
                    } else {
                        $linePoints = $points->map(fn ($point) => "{$point['x']},{$point['y']}")->implode(' ');
                        $areaPath = $points->isNotEmpty()
                            ? 'M ' . $points->map(fn ($point) => "{$point['x']} {$point['y']}")->implode(' L ') . " L {$points->last()['x']} {$bottom} L {$points->first()['x']} {$bottom} Z"
                            : null;
                    }
                @endphp

                <div class="{{ $colors['bg'] }} {{ $colors['border'] }} rounded-lg border p-3 shadow-sm">
                    <div class="mb-2 flex items-center gap-2">
                        <span class="{{ $colors['icon'] }} grid h-8 w-8 shrink-0 place-items-center rounded-lg">
                            <x-filament::icon :icon="$chart['icon']" class="h-4 w-4" />
                        </span>
                        <div class="min-w-0">
                            <h4 class="{{ $colors['text'] }} truncate text-xs font-semibold">{{ $chart['title'] }}</h4>
                            <p class="truncate text-xs text-slate-500 dark:text-slate-400">{{ $chart['subtitle'] }}</p>
                        </div>
                    </div>

                    <div class="relative h-[120px] overflow-hidden rounded-md bg-slate-50/60 dark:bg-slate-950/40">
                        @if ($values->isEmpty())
                            <div class="flex h-full items-center justify-center text-xs font-medium text-slate-400">
                                Belum ada data
                            </div>
                        @else
                            <svg
                                aria-label="Grafik {{ $chart['title'] }}"
                                class="h-full w-full"
                                preserveAspectRatio="none"
                                role="img"
                                viewBox="0 0 {{ $width }} {{ $height }}"
                            >
                                <line x1="{{ $paddingX }}" x2="{{ $width - $paddingX }}" y1="{{ $paddingTop }}" y2="{{ $paddingTop }}" class="text-slate-200 dark:text-slate-800" stroke="currentColor" stroke-width="1" />
                                <line x1="{{ $paddingX }}" x2="{{ $width - $paddingX }}" y1="{{ ($paddingTop + $bottom) / 2 }}" y2="{{ ($paddingTop + $bottom) / 2 }}" class="text-slate-200 dark:text-slate-800" stroke="currentColor" stroke-width="1" />
                                <line x1="{{ $paddingX }}" x2="{{ $width - $paddingX }}" y1="{{ $bottom }}" y2="{{ $bottom }}" class="text-slate-300 dark:text-slate-700" stroke="currentColor" stroke-width="1" />

                                @if ($areaPath)
                                    <path d="{{ $areaPath }}" class="{{ $colors['fill'] }}" fill="currentColor" opacity="0.72" />
                                @endif

                                <polyline
                                    class="{{ $colors['stroke'] }}"
                                    fill="none"
                                    points="{{ $linePoints }}"
                                    stroke="currentColor"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="3"
                                    vector-effect="non-scaling-stroke"
                                />

                                @foreach ($points as $index => $point)
                                    <circle
                                        class="{{ $colors['stroke'] }}"
                                        cx="{{ $point['x'] }}"
                                        cy="{{ $point['y'] }}"
                                        fill="currentColor"
                                        r="{{ $index === $points->count() - 1 ? 4 : 2.5 }}"
                                        vector-effect="non-scaling-stroke"
                                    >
                                        <title>{{ $labels[$index] ?? '' }}: {{ $values[$index] }}{{ $unit === '%' ? '%' : ($unit ? ' ' . $unit : '') }}</title>
                                    </circle>
                                @endforeach
                            </svg>
                        @endif
                    </div>

                    <div class="{{ $colors['border'] }} mt-2 flex items-center justify-between border-t pt-2 text-xs">
                        <span class="text-slate-500 dark:text-slate-400">
                            Terbaru <strong class="{{ $colors['text'] }}">{{ $latest }}{{ $unit === '%' ? '%' : ($unit ? ' ' . $unit : '') }}</strong>
                        </span>
                        <span class="text-slate-500 dark:text-slate-400">
                            Rata-rata <strong class="{{ $colors['text'] }}">{{ $avg }}{{ $unit === '%' ? '%' : ($unit ? ' ' . $unit : '') }}</strong>
                        </span>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</x-filament-widgets::widget>
