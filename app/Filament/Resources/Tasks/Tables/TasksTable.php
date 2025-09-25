<?php

namespace App\Filament\Resources\Tasks\Tables;

use App\Models\Task;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class TasksTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([

//                TextColumn::make('parent_id')
//                    ->label('Батьківське завдання')
//                    ->getStateUsing(fn ($record) => $record->parent ? $record->parent->title : '-')
//                    ->sortable(),
                TextColumn::make('project_id')
                    ->label('Проєкт')
                    ->getStateUsing(fn ($record) => $record->project ? $record->project->name : '-')
                    ->sortable(),
                TextColumn::make('title')
                    ->label('Назва')
                    ->limit(250)
                    ->searchable(),
                TextColumn::make('user_id')
                    ->label('Відповідальний')
                    ->getStateUsing(fn ($record) => $record->user ? $record->user->name : '-')
                    ->sortable(),
                ToggleColumn::make('is_completed')
                    ->label('Завершено'),
                TextColumn::make('status')
                    ->label('Статус')
                    ->getStateUsing(fn ($record) => $record->status ? Task::$statuses[$record->status] : '-')
                    ->searchable(),
                TextColumn::make('priority')
                    ->label('Пріоритет')
                    ->getStateUsing(fn ($record) => $record->priority ? Task::$priorities[$record->priority] : '-')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                TextColumn::make('deadline')
                    ->label('Дедлайн')
                    ->date()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                TextColumn::make('budget')
                    ->label('Бюджет')
                    ->numeric()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                TextColumn::make('spent')
                    ->label('Витрачено')
                    ->numeric()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                TextColumn::make('progress')
                    ->label('Прогрес (%)')
                    ->numeric()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                TextColumn::make('start_date')
                    ->label('Початок')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                TextColumn::make('end_date')
                    ->label('Завершення')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Створено')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Оновлено')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
