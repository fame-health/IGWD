@php
    $selectedSegment = $this->getSelectedSegment();
    $cards = $this->getUserSegmentCards();
    $selectedCard = collect($cards)->firstWhere('key', $selectedSegment);
    $totalUsers = collect($cards)->sum('count');

    $styles = [
        'primary' => [
            'bar' => 'bg-primary-500',
            'active' => 'border-primary-500 bg-primary-50 ring-primary-500/20 dark:border-primary-400 dark:bg-primary-500/10',
            'idle' => 'border-gray-200 bg-white hover:border-primary-300 hover:bg-primary-50/50 dark:border-gray-700 dark:bg-gray-900 dark:hover:border-primary-500 dark:hover:bg-primary-500/10',
            'icon' => 'bg-primary-600 text-white',
            'softIcon' => 'bg-primary-50 text-primary-700 dark:bg-primary-500/10 dark:text-primary-300',
            'text' => 'text-primary-700 dark:text-primary-300',
        ],
        'success' => [
            'bar' => 'bg-emerald-500',
            'active' => 'border-emerald-500 bg-emerald-50 ring-emerald-500/20 dark:border-emerald-400 dark:bg-emerald-500/10',
            'idle' => 'border-gray-200 bg-white hover:border-emerald-300 hover:bg-emerald-50/50 dark:border-gray-700 dark:bg-gray-900 dark:hover:border-emerald-500 dark:hover:bg-emerald-500/10',
            'icon' => 'bg-emerald-600 text-white',
            'softIcon' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300',
            'text' => 'text-emerald-700 dark:text-emerald-300',
        ],
        'info' => [
            'bar' => 'bg-sky-500',
            'active' => 'border-sky-500 bg-sky-50 ring-sky-500/20 dark:border-sky-400 dark:bg-sky-500/10',
            'idle' => 'border-gray-200 bg-white hover:border-sky-300 hover:bg-sky-50/50 dark:border-gray-700 dark:bg-gray-900 dark:hover:border-sky-500 dark:hover:bg-sky-500/10',
            'icon' => 'bg-sky-600 text-white',
            'softIcon' => 'bg-sky-50 text-sky-700 dark:bg-sky-500/10 dark:text-sky-300',
            'text' => 'text-sky-700 dark:text-sky-300',
        ],
    ];
@endphp

<div class="user-segment-picker rounded-lg border border-gray-200 bg-white/95 p-3 shadow-sm dark:border-gray-700 dark:bg-gray-900/95">
    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <div class="flex min-w-0 items-center gap-3">
            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-gray-950 text-white dark:bg-gray-100 dark:text-gray-950">
                <x-filament::icon :icon="$selectedCard['icon'] ?? 'heroicon-o-users'" class="h-5 w-5" />
            </span>

            <div class="min-w-0">
                <p class="text-xs font-semibold uppercase tracking-normal text-gray-500 dark:text-gray-400">
                    Role user
                </p>
                <h3 class="truncate text-base font-semibold text-gray-950 dark:text-white">
                    {{ $selectedCard['title'] ?? 'Pilih role user' }}
                </h3>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2 text-xs font-medium text-gray-600 dark:text-gray-300">
            <span class="inline-flex items-center gap-1.5 rounded-md bg-gray-50 px-2 py-1 ring-1 ring-gray-200 dark:bg-gray-800 dark:ring-gray-700">
                <x-filament::icon icon="heroicon-o-users" class="h-4 w-4" />
                Total {{ number_format($totalUsers) }}
            </span>

            @if ($selectedCard)
                <button
                    type="button"
                    wire:click="clearUserSegment"
                    wire:loading.attr="disabled"
                    wire:target="clearUserSegment"
                    class="inline-flex h-7 w-7 items-center justify-center rounded-md text-gray-500 ring-1 ring-gray-200 transition hover:bg-gray-50 hover:text-gray-950 dark:text-gray-400 dark:ring-gray-700 dark:hover:bg-gray-800 dark:hover:text-white"
                    title="Bersihkan pilihan"
                >
                    <x-filament::icon icon="heroicon-o-x-mark" class="h-4 w-4" />
                </button>
            @endif
        </div>
    </div>

    <div class="mt-3 grid gap-2 lg:grid-cols-3">
        @foreach ($cards as $card)
            @php
                $isActive = $selectedSegment === $card['key'];
                $cardStyle = $styles[$card['color']] ?? $styles['primary'];
                $target = "selectUserSegment('{$card['key']}')";
            @endphp

            <button
                type="button"
                wire:key="user-segment-{{ $card['key'] }}"
                wire:click="selectUserSegment('{{ $card['key'] }}')"
                wire:loading.attr="disabled"
                wire:target="{{ $target }}"
                @class([
                    'group relative overflow-hidden rounded-lg border px-3 py-2.5 text-left shadow-sm outline-none ring-0 transition duration-150 focus-visible:ring-2 focus-visible:ring-primary-500/40 active:scale-[0.99]',
                    'ring-2' => $isActive,
                    $isActive ? $cardStyle['active'] : $cardStyle['idle'],
                ])
            >
                <div @class(['absolute inset-x-0 top-0 h-0.5', $cardStyle['bar']])></div>

                <div class="flex min-w-0 items-center gap-3">
                    <span @class([
                        'flex h-10 w-10 shrink-0 items-center justify-center rounded-lg',
                        $isActive ? $cardStyle['icon'] : $cardStyle['softIcon'],
                    ])>
                        <x-filament::icon :icon="$card['icon']" class="h-5 w-5" />
                    </span>

                    <div class="min-w-0 flex-1">
                        <div class="flex min-w-0 items-center justify-between gap-2">
                            <h3 class="truncate text-sm font-semibold text-gray-950 dark:text-white">
                                {{ $card['title'] }}
                            </h3>

                            <span @class([
                                'shrink-0 rounded-md px-2 py-0.5 text-xs font-semibold',
                                $isActive ? $cardStyle['softIcon'] : 'bg-gray-50 text-gray-600 ring-1 ring-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:ring-gray-700',
                            ])>
                                {{ number_format($card['count']) }}
                            </span>
                        </div>

                        <p class="mt-1 truncate text-xs text-gray-500 dark:text-gray-400">
                            {{ $card['meta'] }}
                        </p>
                    </div>

                    <span @class([
                        'flex h-8 w-8 shrink-0 items-center justify-center rounded-lg transition duration-150 group-hover:translate-x-0.5',
                        $isActive ? $cardStyle['icon'] : 'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400',
                    ])>
                        <x-filament::icon
                            :icon="$isActive ? 'heroicon-o-check' : 'heroicon-o-arrow-right'"
                            class="h-4 w-4"
                        />
                    </span>
                </div>

                <div
                    class="absolute inset-0 hidden items-center justify-center bg-white/85 backdrop-blur-sm dark:bg-gray-950/80"
                    wire:loading.delay.flex
                    wire:target="{{ $target }}"
                >
                    <span @class([
                        'inline-flex items-center gap-2 rounded-md px-3 py-1.5 text-xs font-semibold shadow-sm',
                        $cardStyle['icon'],
                    ])>
                        <x-filament::icon icon="heroicon-o-arrow-path" class="h-4 w-4 animate-spin" />
                        Memuat
                    </span>
                </div>
            </button>
        @endforeach
    </div>
</div>
