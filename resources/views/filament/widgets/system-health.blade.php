@php
    $statusConfig = [
        'excellent' => [
            'color' => 'emerald',
            'label' => 'Excellent',
            'description' => 'Sistem beroperasi optimal',
            'icon' => 'heroicon-o-check-circle',
            'circleClass' => 'text-emerald-500',
            'textClass' => 'text-emerald-600 dark:text-emerald-400',
        ],
        'good' => [
            'color' => 'blue',
            'label' => 'Good',
            'description' => 'Sistem beroperasi dengan baik',
            'icon' => 'heroicon-o-check-badge',
            'circleClass' => 'text-blue-500',
            'textClass' => 'text-blue-600 dark:text-blue-400',
        ],
        'fair' => [
            'color' => 'amber',
            'label' => 'Fair',
            'description' => 'Perlu perhatian',
            'icon' => 'heroicon-o-exclamation-triangle',
            'circleClass' => 'text-amber-500',
            'textClass' => 'text-amber-600 dark:text-amber-400',
        ],
        'poor' => [
            'color' => 'rose',
            'label' => 'Poor',
            'description' => 'Memerlukan tindakan segera',
            'icon' => 'heroicon-o-x-circle',
            'circleClass' => 'text-rose-500',
            'textClass' => 'text-rose-600 dark:text-rose-400',
        ],
    ];
    
    $status = $statusConfig[$healthStatus] ?? $statusConfig['fair'];
    
    $metricColors = [
        'success' => 'text-emerald-700 dark:text-emerald-300',
        'info' => 'text-blue-700 dark:text-blue-300',
        'warning' => 'text-amber-700 dark:text-amber-300',
        'danger' => 'text-rose-700 dark:text-rose-300',
    ];
    
    $circumference = 2 * 3.14159 * 40;
    $dashOffset = $circumference * (1 - $healthScore / 100);
@endphp

<x-filament-widgets::widget>
    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-slate-900">
        <div class="flex flex-col gap-6 md:flex-row md:items-center md:justify-between">
            {{-- Health Score --}}
            <div class="flex items-center gap-5">
                <div class="relative">
                    {{-- Circular progress --}}
                    <svg class="h-24 w-24 -rotate-90 transform">
                        <circle
                            cx="48"
                            cy="48"
                            r="40"
                            stroke="currentColor"
                            stroke-width="8"
                            fill="none"
                            class="text-slate-200 dark:text-slate-700"
                        />
                        <circle
                            cx="48"
                            cy="48"
                            r="40"
                            stroke="currentColor"
                            stroke-width="8"
                            fill="none"
                            stroke-dasharray="{{ $circumference }}"
                            stroke-dashoffset="{{ $dashOffset }}"
                            class="{{ $status['circleClass'] }} transition-all duration-1000"
                            stroke-linecap="round"
                        />
                    </svg>
                    
                    {{-- Score in center --}}
                    <div class="absolute inset-0 flex items-center justify-center">
                        <div class="text-center">
                            <p class="{{ $status['textClass'] }} text-2xl font-bold">
                                {{ $healthScore }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="space-y-1">
                    <div class="flex items-center gap-2">
                        <x-filament::icon 
                            :icon="$status['icon']" 
                            class="h-6 w-6 {{ $status['circleClass'] }}"
                        />
                        <h3 class="text-xl font-bold text-slate-900 dark:text-white">
                            System Health: {{ $status['label'] }}
                        </h3>
                    </div>
                    <p class="text-sm text-slate-600 dark:text-slate-400">
                        {{ $status['description'] }}
                    </p>
                    <p class="text-xs text-slate-500 dark:text-slate-500">
                        Last update: {{ $lastUpdate }}
                    </p>
                </div>
            </div>

            {{-- Metrics --}}
            <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                @foreach ($metrics as $metric)
                    <div class="space-y-2 text-center">
                        <div class="flex items-center justify-center">
                            <x-filament::icon 
                                :icon="$metric['icon']" 
                                class="h-8 w-8 {{ $metricColors[$metric['status']] ?? $metricColors['info'] }}"
                            />
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-slate-900 dark:text-white">
                                {{ $metric['value'] }}
                            </p>
                            <p class="text-xs font-semibold text-slate-700 dark:text-slate-300">
                                {{ $metric['label'] }}
                            </p>
                            <p class="text-xs text-slate-500 dark:text-slate-400">
                                {{ $metric['subtext'] }}
                            </p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-filament-widgets::widget>
