<?php

namespace App\Filament\Resources\ContractorActOfCompletedWorks;

use App\Filament\Resources\ContractorActOfCompletedWorks\Pages\CreateContractorActOfCompletedWork;
use App\Filament\Resources\ContractorActOfCompletedWorks\Pages\EditContractorActOfCompletedWork;
use App\Filament\Resources\ContractorActOfCompletedWorks\Pages\ListContractorActOfCompletedWorks;
use App\Filament\Resources\ContractorActOfCompletedWorks\Pages\ViewContractorActOfCompletedWork;
use App\Filament\Resources\ContractorActOfCompletedWorks\RelationManagers;
use App\Filament\Resources\ContractorActOfCompletedWorks\Schemas\ContractorActOfCompletedWorkForm;
use App\Filament\Resources\ContractorActOfCompletedWorks\Schemas\ContractorActOfCompletedWorkInfolist;
use App\Filament\Resources\ContractorActOfCompletedWorks\Tables\ContractorActOfCompletedWorksTable;
use App\Models\ContractorActOfCompletedWork;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ContractorActOfCompletedWorkResource extends Resource
{
    protected static ?string $model = ContractorActOfCompletedWork::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'number';

    public static function getNavigationLabel(): string
    {
        return 'Акти виконаних робіт';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Акти виконаних робіт';
    }

    public static function getModelLabel(): string
    {
        return 'Акт виконаних робіт';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Фінанси';
    }

    public static function form(Schema $schema): Schema
    {
        return ContractorActOfCompletedWorkForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ContractorActOfCompletedWorkInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ContractorActOfCompletedWorksTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListContractorActOfCompletedWorks::route('/'),
            'create' => CreateContractorActOfCompletedWork::route('/create'),
            'view' => ViewContractorActOfCompletedWork::route('/{record}'),
            'edit' => EditContractorActOfCompletedWork::route('/{record}/edit'),
        ];
    }
}
