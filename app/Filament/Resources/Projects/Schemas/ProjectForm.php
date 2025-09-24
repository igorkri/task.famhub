<?php

namespace App\Filament\Resources\Projects\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ProjectForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('gid'),
                TextInput::make('icon'),
                TextInput::make('name')
                    ->required(),
                TextInput::make('description'),
                TextInput::make('workspace_id')
                    ->required()
                    ->numeric(),
                Toggle::make('is_active')
                    ->required(),
                Toggle::make('is_favorite')
                    ->required(),
                Toggle::make('is_archived')
                    ->required(),
                TextInput::make('sort')
                    ->required()
                    ->numeric()
                    ->default(0),
            ]);
    }
}
