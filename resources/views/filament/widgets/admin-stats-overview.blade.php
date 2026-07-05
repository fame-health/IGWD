@php
    $colors = [
        'success' => [
            'border' => 'border-emerald-200 dark:border-emerald-800',
            'bg' => 'bg-white dark:bg-slate-900',
            'icon' => 'bg-emerald-500 text-white',
            'label' => 'text-emerald-700 dark:text-emerald-300',
        ],
        'info' => [
            'border' => 'border-sky-200 dark:border-sky-800',
            'bg' => 'bg-white dark:bg-slate-900',
            'icon' => 'bg-sky-500 text-white',
            'label' => 'text-sky-700 dark:text-sky-300',
        ],
        'warning' => [
            'border' => 'border-amber-200 dark:border-amber-800',
            'bg' => 'bg-white dark:bg-slate-900',
            'icon' => 'bg-amber-500 text-white',
            'label' => 'text-amber-700 dark:text-amber-300',
        ],
        'danger' => [
            'border' => 'border-rose-200 dark:border-rose-800',
            'bg' => 'bg-white dark:bg-slate-900',
            'icon' => 'bg-rose-500 text-white',
            'label' => 'text-rose-700 dark:text-rose-300',
        ],
        'slate' => [
            'border' => 'border-slate-200 dark:border-slate-700',
            'bg' => 'bg-white dark:bg-slate-900',
            'icon' => 'bg-slate-600 text-white',
            'label' => 'text-slate-700 dark:text-slate-300',
        ],
    ];

    $chipColors = [
        'info' => 'border-sky-200 bg-sky-50 text-sky-700 dark:border-sky-800 dark:bg-sky-950/30 dark:text-sky-300',
        'warning' => 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-800 dark:bg-amber-950/30 dark:text-amber-300',
        'danger' => 'border-rose-200 bg-rose-50 text-rose-700 dark:border-rose-800 dark:bg-rose-950/30 dark:text-rose-300',
        'success' => 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-800 dark:bg-emerald-950/30 dark:text-emerald-300',
    ];
@endphp

<x-filament-widgets::widget>
    <div class="space-y-3">
        <div class="grid gap-3 xl:grid-cols-[minmax(16rem,0.95fr)_2fr]">
            <a href="{{ $headline['url'] }}" wire:navigate class="group block rounded-lg border border-rose-200 bg-rose-50 p-3 shadow-sm transition hover:border-rose-300 hover:bg-rose-100/70 dark:border-rose-800 dark:bg-rose-950/30 dark:hover:bg-rose-950/45">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-xs font-semibold uppercase text-rose-700 dark:text-rose-300">
                            Prioritas tinggi
                        </p>
                        <div class="mt-1 flex items-baseline gap-2">
                            <span class="text-3xl font-bold leading-none text-rose-950 dark:text-rose-50">
                                {{ number_format($headline['value']) }}
                            </span>
                            <span class="text-xs font-medium text-rose-700 dark:text-rose-300">
                                pasien
                            </span>
                        </div>
                        <p class="mt-1 text-xs leading-5 text-rose-700/80 dark:text-rose-200/80">
                            {{ $headline['meta'] }}
                        </p>
                    </div>

                    <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-rose-600 text-white shadow-sm">
                        <x-filament::icon :icon="$headline['icon']" class="h-5 w-5" />
                    </span>
                </div>
            </a>

            <div class="grid grid-cols-2 gap-2 lg:grid-cols-4">
                @foreach ($cards as $card)
                    @php
                        $color = $colors[$card['tone']] ?? $colors['slate'];
                        $value = is_numeric($card['value']) ? number_format($card['value']) : $card['value'];
                    @endphp

                    <a href="{{ $card['url'] }}" wire:navigate class="{{ $color['bg'] }} {{ $color['border'] }} group block rounded-lg border px-3 py-2.5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                        <div class="flex items-center justify-between gap-2">
                            <span class="{{ $color['icon'] }} grid h-8 w-8 shrink-0 place-items-center rounded-lg">
                                <x-filament::icon :icon="$card['icon']" class="h-4 w-4" />
                            </span>
                        </div>

                        <p class="{{ $color['label'] }} mt-2 truncate text-xs font-semibold">
                            {{ $card['label'] }}
                        </p>
                        <p class="mt-0.5 text-2xl font-bold leading-none text-slate-950 dark:text-white">
                            {{ $value }}
                        </p>
                        <p class="mt-1 truncate text-xs text-slate-500 dark:text-slate-400">
                            {{ $card['meta'] }}
                        </p>
                    </a>
                @endforeach
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2 text-xs">
            <span class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-2.5 py-1 font-medium text-slate-600 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300">
                <x-filament::icon icon="heroicon-o-clock" class="h-3.5 w-3.5 text-teal-600 dark:text-teal-400" />
                Update {{ $updatedAt }}
            </span>

            @foreach ($followUps as $item)
                @php
                    $chipColor = $chipColors[$item['tone']] ?? $chipColors['info'];
                    $value = is_numeric($item['value']) ? number_format($item['value']) : $item['value'];
                @endphp

                <a href="{{ $item['url'] }}" wire:navigate class="{{ $chipColor }} inline-flex items-center gap-2 rounded-lg border px-2.5 py-1 font-semibold transition hover:opacity-80">
                    <span>{{ $item['label'] }}</span>
                    <span class="rounded bg-white/70 px-1.5 py-0.5 dark:bg-black/20">{{ $value }}</span>
                </a>
            @endforeach
        </div>
    </div>
</x-filament-widgets::widget>
