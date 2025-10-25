<?php

namespace App\Filament\Resources\ActOfWorkDetails\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class ActOfWorkDetailForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('act_of_work_id')
                    ->relationship('actOfWork', 'id')
                    ->required(),
                TextInput::make('time_id')
                    ->numeric(),
                TextInput::make('task_gid'),
                TextInput::make('project_gid'),
                TextInput::make('project'),
                TextInput::make('task'),
                Textarea::make('description')
                    ->columnSpanFull(),
                TextInput::make('amount')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                TextInput::make('hours')
                    ->required()
                    ->numeric()
                    ->default(0.0),
            ]);
    }
}
