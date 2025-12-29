<?php

namespace App\Filament\Resources\Projects\Schemas;

use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Flex;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ProjectForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Flex::make([
                    // Левая часть: контент
                    Section::make('Основное')
                        ->schema([
                            Select::make('workspace_id')
                                ->label('Робочий простір')
                                ->relationship('workspace', 'name')
                                ->required(),
                            TextInput::make('name')
                                ->label('Назва')
                                ->required()
                                ->maxLength(255),

                            RichEditor::make('description')
                                ->label('Опис'),
                        ])
                        ->grow(), // занимает всё доступное пространство

                    // Правая часть:
                    Section::make('Додатково')
                        ->schema([
                            Toggle::make('is_active')->label('Активний проект')->default(true)->inline(false),
                            Toggle::make('is_favorite')->label('Вибране')->default(false)->inline(false),
                            Toggle::make('is_archived')->label('Архівований')->default(false)->inline(false),
                            TextInput::make('asana_id')
                                ->label('Project GID')
                                ->helperText('Ідентифікатор проекту в Asana')
                                ->maxLength(255),
                        ])
                        ->grow(false)
                        ->columns(1),
                ]),
            ])
            ->columns(1);
    }
}
