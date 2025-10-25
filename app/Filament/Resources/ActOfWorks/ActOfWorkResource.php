<?php

namespace App\Filament\Resources\ActOfWorks;

use App\Filament\Resources\ActOfWorks\Pages\CreateActOfWork;
use App\Filament\Resources\ActOfWorks\Pages\EditActOfWork;
use App\Filament\Resources\ActOfWorks\Pages\ListActOfWorks;
use App\Filament\Resources\ActOfWorks\RelationManagers;
use App\Filament\Resources\ActOfWorks\Schemas\ActOfWorkForm;
use App\Filament\Resources\ActOfWorks\Tables\ActOfWorksTable;
use App\Models\ActOfWork;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ActOfWorkResource extends Resource
{
    protected static ?string $model = ActOfWork::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'number';

    public static function form(Schema $schema): Schema
    {
        return ActOfWorkForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ActOfWorksTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\DetailsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListActOfWorks::route('/'),
            'create' => CreateActOfWork::route('/create'),
            'edit' => EditActOfWork::route('/{record}/edit'),
        ];
    }
}
