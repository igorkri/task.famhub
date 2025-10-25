<?php

namespace App\Filament\Resources\ActOfWorks\RelationManagers;

use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class DetailsRelationManager extends RelationManager
{
    protected static string $relationship = 'details';

    protected static ?string $title = 'Деталі акту';

    protected static ?string $recordTitleAttribute = 'task';

    public function schema(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(3)
                    ->schema([
                        TextInput::make('time_id')
                            ->label('Time ID')
                            ->numeric()
                            ->disabled(),

                        TextInput::make('task_gid')
                            ->label('Task GID')
                            ->disabled(),

                        TextInput::make('project_gid')
                            ->label('Project GID')
                            ->disabled(),
                    ]),

                Grid::make(2)
                    ->schema([
                        TextInput::make('project')
                            ->label('Проект')
                            ->disabled(),

                        TextInput::make('task')
                            ->label('Задача')
                            ->disabled(),
                    ]),

                Textarea::make('description')
                    ->label('Опис')
                    ->rows(3)
                    ->disabled()
                    ->columnSpanFull(),

                Grid::make(2)
                    ->schema([
                        TextInput::make('hours')
                            ->label('Години')
                            ->numeric()
                            ->disabled()
                            ->suffix('год'),

                        TextInput::make('amount')
                            ->label('Сума')
                            ->numeric()
                            ->disabled()
                            ->prefix('₴'),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('task')
            ->columns([
                Tables\Columns\TextColumn::make('time_id')
                    ->label('Time ID')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('project')
                    ->label('Проект')
                    ->sortable()
                    ->searchable()
                    ->wrap()
                    ->weight('medium'),

                Tables\Columns\TextColumn::make('task')
                    ->label('Задача')
                    ->sortable()
                    ->searchable()
                    ->wrap()
                    ->limit(50),

                Tables\Columns\TextColumn::make('description')
                    ->label('Опис')
                    ->limit(40)
                    ->wrap()
                    ->toggleable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('hours')
                    ->label('Години')
                    ->numeric(decimalPlaces: 2)
                    ->sortable()
                    ->alignEnd()
                    ->suffix(' год'),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Сума')
                    ->money('UAH')
                    ->sortable()
                    ->alignEnd()
                    ->summarize([
                        Tables\Columns\Summarizers\Sum::make()
                            ->money('UAH')
                            ->label('Всього'),
                    ]),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                // Tables\Actions\CreateAction::make(), // Отключено, т.к. детали импортируются автоматически
            ])
            ->recordActions([
                // ViewAction::make(),
                // EditAction::make(), // Отключено
                // Tables\Actions\DeleteAction::make(), // Отключено
            ])
            ->toolbarActions([
                // Tables\Actions\BulkActionGroup::make([
                    // Tables\Actions\DeleteBulkAction::make(),
                // ]),
            ])
            ->defaultSort('time_id', 'asc')
            ->paginated([10, 25, 50, 150, 300, 500, 1000]);
    }
}

