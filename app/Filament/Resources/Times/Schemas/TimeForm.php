<?php

namespace App\Filament\Resources\Times\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class TimeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('task_id')
                    ->required()
                    ->numeric(),
                TextInput::make('user_id')
                    ->required()
                    ->numeric(),
                TextInput::make('title'),
                Textarea::make('description')
                    ->columnSpanFull(),
                TextInput::make('coefficient')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                TextInput::make('duration')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('status')
                    ->required()
                    ->default('in_progress'),
                TextInput::make('report_status')
                    ->required()
                    ->default('not_submitted'),
                Toggle::make('is_archived')
                    ->required(),
            ]);
    }
}
