<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;

class ManageUsers extends ManageRecords
{
    protected static string $resource = UserResource::class;

    #[Url(as: 'jenis')]
    public ?string $userSegment = null;

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                View::make('filament.resources.users.pages.partials.user-segment-picker'),
                EmbeddedTable::make(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading(fn (): string => $this->getSelectedSegmentTitle() ?? 'Daftar User')
            ->description(fn (): string => $this->getSelectedSegmentDescription() ?? 'Pilih kategori data terlebih dahulu untuk menampilkan daftar user.')
            ->modifyQueryUsing(function (Builder $query): Builder {
                $query
                    ->select(['id', 'name', 'email', 'role', 'patient_id', 'is_active', 'created_at'])
                    ->with(['patient:id,name']);

                return match ($this->getSelectedSegment()) {
                    'patients' => $query->where('role', 'pasien'),
                    'nurses' => $query->where('role', 'perawat'),
                    'doctors' => $query->where('role', 'dokter'),
                    default => $query->whereRaw('1 = 0'),
                };
            });
    }

    public function selectUserSegment(string $segment): void
    {
        if (! in_array($segment, ['patients', 'nurses', 'doctors'], true)) {
            return;
        }

        $this->userSegment = $segment;
        $this->resetTable();
    }

    public function clearUserSegment(): void
    {
        $this->userSegment = null;
        $this->resetTable();
    }

    public function getSelectedSegment(): ?string
    {
        return in_array($this->userSegment, ['patients', 'nurses', 'doctors'], true)
            ? $this->userSegment
            : null;
    }

    public function getSelectedSegmentTitle(): ?string
    {
        return match ($this->getSelectedSegment()) {
            'patients' => 'Daftar User Pasien',
            'nurses' => 'Daftar User Perawat',
            'doctors' => 'Daftar User Dokter',
            default => null,
        };
    }

    public function getSelectedSegmentDescription(): ?string
    {
        return match ($this->getSelectedSegment()) {
            'patients' => 'Akun login yang terhubung ke data pasien dan aplikasi Android pasien.',
            'nurses' => 'Akun perawat untuk mengelola monitoring, jadwal, dan data tindakan pasien.',
            'doctors' => 'Akun dokter untuk memantau kondisi pasien, sesi HD, dan tindak lanjut risiko.',
            default => null,
        };
    }

    public function getSelectedSegmentIcon(): Heroicon
    {
        return match ($this->getSelectedSegment()) {
            'patients' => Heroicon::OutlinedUserGroup,
            'nurses' => Heroicon::OutlinedIdentification,
            'doctors' => Heroicon::OutlinedAcademicCap,
            default => Heroicon::OutlinedTableCells,
        };
    }

    public function getSelectedSegmentColor(): string
    {
        return match ($this->getSelectedSegment()) {
            'patients' => 'primary',
            'nurses' => 'success',
            'doctors' => 'info',
            default => 'gray',
        };
    }

    public function getUserSegmentCards(): array
    {
        $counts = $this->getUserSegmentCounts();

        return [
            [
                'key' => 'patients',
                'title' => 'Data Pasien',
                'description' => 'Akun pasien yang terhubung ke profil pasien, monitoring, dan notifikasi aplikasi.',
                'icon' => Heroicon::OutlinedUserGroup,
                'count' => $counts['pasien'] ?? 0,
                'meta' => 'pasien',
                'color' => 'primary',
            ],
            [
                'key' => 'nurses',
                'title' => 'Perawat',
                'description' => 'Akun perawat untuk input tindakan, monitoring harian, dan pengelolaan jadwal pasien.',
                'icon' => Heroicon::OutlinedIdentification,
                'count' => $counts['perawat'] ?? 0,
                'meta' => 'perawat',
                'color' => 'success',
            ],
            [
                'key' => 'doctors',
                'title' => 'Dokter',
                'description' => 'Akun dokter untuk review kondisi klinis, catatan sesi HD, dan follow-up risiko.',
                'icon' => Heroicon::OutlinedAcademicCap,
                'count' => $counts['dokter'] ?? 0,
                'meta' => 'dokter',
                'color' => 'info',
            ],
        ];
    }

    private function getUserSegmentCounts(): array
    {
        return User::query()
            ->selectRaw('role, count(*) as aggregate')
            ->whereIn('role', ['pasien', 'perawat', 'dokter'])
            ->groupBy('role')
            ->pluck('aggregate', 'role')
            ->map(fn (mixed $count): int => (int) $count)
            ->all();
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
