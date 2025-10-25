<?php

namespace App\Filament\Resources\ActOfWorkDetails;

use App\Filament\Resources\ActOfWorkDetails\Pages\CreateActOfWorkDetail;
use App\Filament\Resources\ActOfWorkDetails\Pages\EditActOfWorkDetail;
use App\Filament\Resources\ActOfWorkDetails\Pages\ListActOfWorkDetails;
use App\Filament\Resources\ActOfWorkDetails\Schemas\ActOfWorkDetailForm;
use App\Filament\Resources\ActOfWorkDetails\Tables\ActOfWorkDetailsTable;
use App\Models\ActOfWorkDetail;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ActOfWorkDetailResource extends Resource
{
    protected static ?string $model = ActOfWorkDetail::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'task';

    public static function form(Schema $schema): Schema
    {
        return ActOfWorkDetailForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ActOfWorkDetailsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListActOfWorkDetails::route('/'),
            'create' => CreateActOfWorkDetail::route('/create'),
            'edit' => EditActOfWorkDetail::route('/{record}/edit'),
        ];
    }
}
