@php
    $colorClasses = [
        'success' => [
            'bg' => 'bg-gradient-to-br from-emerald-50 to-emerald-100/60 dark:from-emerald-950/40 dark:to-emerald-900/30',
            'border' => 'border-emerald-200 dark:border-emerald-400/30',
            'icon' => 'bg-gradient-to-br from-emerald-500 to-emerald-600 text-white shadow-lg shadow-emerald-500/30',
            'text' => 'text-emerald-700 dark:text-emerald-300',
            'value' => 'text-emerald-900 dark:text-emerald-100',
        ],
        'info' => [
            'bg' => 'bg-gradient-to-br from-blue-50 to-blue-100/60 dark:from-blue-950/40 dark:to-blue-900/30',
            'border' => 'border-blue-200 dark:border-blue-400/30',
            'icon' => 'bg-gradient-to-br from-blue-500 to-blue-600 text-white shadow-lg shadow-blue-500/30',
            'text' => 'text-blue-700 dark:text-blue-300',
            'value' => 'text-blue-900 dark:text-blue-100',
        ],
        'warning' => [
            'bg' => 'bg-gradient-to-br from-amber-50 to-amber-100/60 dark:from-amber-950/40 dark:to-amber-900/30',
            'border' => 'border-amber-200 dark:border-amber-400/30',
            'icon' => 'bg-gradient-to-br from-amber-500 to-amber-600 text-white shadow-lg shadow-amber-500/30',
            'text' => 'text-amber-700 dark:text-amber-300',
            'value' => 'text-amber-900 dark:text-amber-100',
        ],
        'danger' => [
            'bg' => 'bg-gradient-to-br from-rose-50 to-rose-100/60 dark:from-rose-950/40 dark:to-rose-900/30',
            'border' => 'border-rose-200 dark:border-rose-400/30',
            'icon' => 'bg-gradient-to-br from-rose-500 to-rose-600 text-white shadow-lg shadow-rose-500/30',
            'text' => 'text-rose-700 dark:text-rose-300',
            'value' => 'text-rose-900 dark:text-rose-100',
        ],
    ];
@endphp

<x-filament-widgets::widget>
    <div class="space-y-4">
        {{-- Header --}}
        <div class="flex items-center gap-2">
            <svg class="h-5 w-5 text-violet-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
            </svg>
            <h3 class="text-base font-bold text-slate-900 dark:text-white">
                Insight Cepat
            </h3>
        </div>

        {{-- Insights Grid --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
            @foreach ($insights as $insight)
                @php($colors = $colorClasses[$insight['color']] ?? $colorClasses['info'])

                <div class="{{ $colors['bg'] }} {{ $colors['border'] }} group relative overflow-hidden rounded-xl border p-4 shadow-sm transition-all duration-300 hover:scale-105 hover:shadow-lg">
                    <div class="relative space-y-3">
                        <div class="flex items-start justify-between gap-2">
                            <span class="{{ $colors['icon'] }} grid h-10 w-10 shrink-0 place-items-center rounded-lg transition-transform duration-300 group-hover:rotate-6 group-hover:scale-110">
                                <x-filament::icon :icon="$insight['icon']" class="h-5 w-5" />
                            </span>
                        </div>

                        <div class="space-y-1">
                            <p class="{{ $colors['text'] }} text-xs font-semibold uppercase tracking-wide">
                                {{ $insight['label'] }}
                            </p>
                            <div class="flex items-end gap-1.5">
                                <p class="{{ $colors['value'] }} text-3xl font-bold tracking-tight">
                                    {{ number_format($insight['value'], is_float($insight['value']) ? 1 : 0) }}
                                </p>
                                <p class="{{ $colors['text'] }} mb-0.5 text-sm font-medium">
                                    {{ $insight['unit'] }}
                                </p>
                            </div>
                            <p class="{{ $colors['text'] }} text-xs opacity-80">
                                {{ $insight['description'] }}
                            </p>
                        </div>
                    </div>

                    {{-- Decorative element --}}
                    <div class="absolute -right-4 -top-4 h-16 w-16 rounded-full bg-white/10 blur-xl transition-opacity group-hover:opacity-70"></div>
                </div>
            @endforeach
        </div>
    </div>
</x-filament-widgets::widget>
