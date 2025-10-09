<?php

namespace App\Filament\Resources\Sections\Schemas;

use App\Models\Project;
use App\Models\Task;
use Filament\Forms;
use Filament\Schemas\Schema;

class SectionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\Select::make('project_id')
                    ->label('Проект')
                    ->options(Project::pluck('name', 'id'))
                    ->required()
                    ->searchable(),
                Forms\Components\TextInput::make('name')
                    ->label('Назва')
                    ->required(),
                Forms\Components\Select::make('status')
                    ->label('Статус')
                    ->options(Task::$statuses)
                    ->placeholder('Оберіть статус для секції'),
                Forms\Components\TextInput::make('asana_gid')
                    ->label('Asana GID')
                    ->disabled(),
            ]);
    }
}
