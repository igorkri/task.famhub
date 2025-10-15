<?php

namespace App\Filament\Resources\ProjectUsers;

use App\Filament\Resources\ProjectUsers\Pages\CreateProjectUser;
use App\Filament\Resources\ProjectUsers\Pages\EditProjectUser;
use App\Filament\Resources\ProjectUsers\Pages\ListProjectUsers;
use App\Filament\Resources\ProjectUsers\Schemas\ProjectUserForm;
use App\Filament\Resources\ProjectUsers\Tables\ProjectUsersTable;
use App\Models\Navigation;
use App\Models\ProjectUser;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class ProjectUserResource extends Resource
{
    protected static ?string $model = ProjectUser::class;

    protected static string|BackedEnum|null $navigationIcon = Navigation::NAVIGATION['PROJECT_USER']['ICON'];

    protected static string|null|\UnitEnum $navigationGroup = Navigation::NAVIGATION['PROJECT_USER']['GROUP'];

    protected static ?int $navigationSort = Navigation::NAVIGATION['PROJECT_USER']['SORT'];

    // назва в меню
    protected static ?string $navigationLabel = Navigation::NAVIGATION['PROJECT_USER']['LABEL'];

    // 👇 ось так правильно
    public static function getModelLabel(): string
    {
        return Navigation::NAVIGATION['PROJECT_USER']['LABEL'];
    }

    public static function getPluralLabel(): string
    {
        return Navigation::NAVIGATION['PROJECT_USER']['LABEL'];
    }

    public static function getNavigationLabel(): string
    {
        return Navigation::NAVIGATION['PROJECT_USER']['LABEL'];
    }

    public static function form(Schema $schema): Schema
    {
        return ProjectUserForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProjectUsersTable::configure($table);
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
            'index' => ListProjectUsers::route('/'),
            'create' => CreateProjectUser::route('/create'),
            'edit' => EditProjectUser::route('/{record}/edit'),
        ];
    }
}
