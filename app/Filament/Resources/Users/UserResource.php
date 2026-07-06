<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\ManageUsers;
use App\Filament\Support\ResourceUi;
use App\Models\User;
use BackedEnum;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?string $modelLabel = 'User';

    protected static ?string $pluralModelLabel = 'User';

    protected static string|\UnitEnum|null $navigationGroup = 'Master Data';

    protected static ?string $navigationLabel = 'User';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components(ResourceUi::form('users'));
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->searchPlaceholder('Cari nama, email, role, atau pasien terkait')
            ->columns([
                ...ResourceUi::columns('users'),
            ])
            ->filters([
                ...ResourceUi::filters('users'),
            ])
            ->defaultPaginationPageOption(10)
            ->paginationPageOptions([10, 25, 50])
            ->emptyStateIcon('heroicon-o-users')
            ->emptyStateHeading('Belum ada user pada kategori ini')
            ->emptyStateDescription('Gunakan tombol tambah user untuk membuat akun baru sesuai kebutuhan operasional.')
            ->recordActions(
                ActionGroup::make([
                    EditAction::make(),
                    DeleteAction::make(),
                ])
                    ->iconButton()
                    ->tooltip('Aksi')
                    ->color('gray')
            )
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageUsers::route('/'),
        ];
    }
}
