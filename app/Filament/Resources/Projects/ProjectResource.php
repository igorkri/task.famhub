<?php

namespace App\Filament\Resources\Projects;

use App\Filament\Resources\Projects\Pages\CreateProject;
use App\Filament\Resources\Projects\Pages\EditProject;
use App\Filament\Resources\Projects\Pages\ListProjects;
use App\Filament\Resources\Projects\Pages\ViewProject;
use App\Filament\Resources\Projects\Schemas\ProjectForm;
use App\Filament\Resources\Projects\Schemas\ProjectInfolist;
use App\Filament\Resources\Projects\Tables\ProjectsTable;
use App\Models\Navigation;
use App\Models\Project;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ProjectResource extends Resource
{
    protected static ?string $model = Project::class;

    protected static string|BackedEnum|null $navigationIcon = Navigation::NAVIGATION['PROJECT']['ICON']; // Heroicon::OutlinedRectangleStack;

    protected static string|null|\UnitEnum $navigationGroup = Navigation::NAVIGATION['PROJECT']['GROUP'];

    protected static ?int $navigationSort = Navigation::NAVIGATION['PROJECT']['SORT'];

    // назва в меню
    protected static ?string $navigationLabel = Navigation::NAVIGATION['PROJECT']['LABEL'];

    // 👇 ось так правильно
    public static function getModelLabel(): string
    {
        return Navigation::NAVIGATION['PROJECT']['LABEL'];
    }

    public static function getPluralLabel(): string
    {
        return Navigation::NAVIGATION['PROJECT']['LABEL'];
    }

    public static function getNavigationLabel(): string
    {
        return Navigation::NAVIGATION['PROJECT']['LABEL'];
    }

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return ProjectForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ProjectInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProjectsTable::configure($table);
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
            'index' => ListProjects::route('/'),
            'create' => CreateProject::route('/create'),
            'view' => ViewProject::route('/{record}'),
            'edit' => EditProject::route('/{record}/edit'),
        ];
    }

    /**
     * Матод для отримання запиту Eloquent з урахуванням політик доступу.
     */
    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        /** @var \Illuminate\Foundation\Auth\User $user */
        $user = auth()->user();
        $query = parent::getEloquentQuery();
        // Ограничение доступа через политику
        if ($user) {
            // Если есть scope или связь, используйте її. Пример:
            // return $query->where('user_id', $user->id);
            // Или через policy:
            // return $query->whereIn('id', $user->accessibleProjectIds());
            // Здесь просто пример, адаптируйте під вашу бізнес-логіку:
            if (method_exists($user, 'accessibleProjectIds')) {
                return $query->whereIn('id', $user->accessibleProjectIds());
            }
        }

        return $query;
    }
}
