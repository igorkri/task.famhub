<?php

namespace App\Filament\Resources\Tasks;

use App\Filament\Resources\Tasks\Pages\CreateTask;
use App\Filament\Resources\Tasks\Pages\EditTask;
use App\Filament\Resources\Tasks\Pages\ListTasks;
use App\Filament\Resources\Tasks\Schemas\TaskForm;
use App\Filament\Resources\Tasks\Tables\TasksTable;
use App\Models\Navigation;
use App\Models\Task;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class TaskResource extends Resource
{
    protected static ?string $model = Task::class;

    protected static string|BackedEnum|null $navigationIcon = Navigation::NAVIGATION['TASK']['ICON'];

    protected static string|null|\UnitEnum $navigationGroup = Navigation::NAVIGATION['TASK']['GROUP'];

    protected static ?int $navigationSort = Navigation::NAVIGATION['TASK']['SORT'];

    protected static ?string $navigationLabel = Navigation::NAVIGATION['TASK']['LABEL'];

    public static function getModelLabel(): string
    {
        return Navigation::NAVIGATION['TASK']['LABEL'];
    }

    public static function getPluralLabel(): string
    {
        return Navigation::NAVIGATION['TASK']['LABEL'];
    }

    public static function getNavigationLabel(): string
    {
        return Navigation::NAVIGATION['TASK']['LABEL'];
    }

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return TaskForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TasksTable::configure($table);
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
            'index' => ListTasks::route('/'),
            'create' => CreateTask::route('/create'),
            'edit' => EditTask::route('/{record}/edit'),
        ];
    }
}
