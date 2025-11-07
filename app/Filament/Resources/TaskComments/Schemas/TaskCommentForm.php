<?php

namespace App\Filament\Resources\TaskComments\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class TaskCommentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('task_id')
                    ->relationship('task', 'title')
                    ->required(),
                Select::make('user_id')
                    ->options(function () {
                        return \App\Models\User::usersList();
                    })
                    ->required(),
                TextInput::make('asana_gid'),
                Textarea::make('content')
                    ->required()
                    ->columnSpanFull(),
                DateTimePicker::make('asana_created_at'),
            ]);
    }
}
