<?php

namespace App\Filament\Resources\ProjectUsers\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ProjectUserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('project_id')
                    ->required()
                    ->numeric(),
                TextInput::make('user_id')
                    ->required()
                    ->numeric(),
                Toggle::make('is_admin')
                    ->required(),
                TextInput::make('role')
                    ->required()
                    ->default('viewer'),
            ]);
    }
}
