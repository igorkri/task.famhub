<?php

namespace App\Filament\Resources\Times;

use App\Filament\Resources\Times\Pages\CreateTime;
use App\Filament\Resources\Times\Pages\EditTime;
use App\Filament\Resources\Times\Pages\ListTimes;
use App\Filament\Resources\Times\Schemas\TimeForm;
use App\Filament\Resources\Times\Tables\TimesTable;
use App\Models\Navigation;
use App\Models\Time;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class TimeResource extends Resource
{
    protected static ?string $model = Time::class;

    protected static string|BackedEnum|null $navigationIcon = Navigation::NAVIGATION['TIME']['ICON'];
    protected static string|null|\UnitEnum $navigationGroup = Navigation::NAVIGATION['TIME']['GROUP'];
    protected static ?int $navigationSort = Navigation::NAVIGATION['TIME']['SORT'];
    protected static ?string $navigationLabel = Navigation::NAVIGATION['TIME']['LABEL'];
    public static function getModelLabel(): string
    {
        return Navigation::NAVIGATION['TIME']['LABEL'];
    }

    public static function getPluralLabel(): string
    {
        return Navigation::NAVIGATION['TIME']['LABEL'];
    }

    public static function getNavigationLabel(): string
    {
        return Navigation::NAVIGATION['TIME']['LABEL'];
    }

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return TimeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TimesTable::configure($table);
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
            'index' => ListTimes::route('/'),
            'create' => CreateTime::route('/create'),
            'edit' => EditTime::route('/{record}/edit'),
        ];
    }
}
