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
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Builder;

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
                SelectFilter::make('project_id')
                    ->label('Проєкт')
                    ->relationship('project', 'name'),
                SelectFilter::make('user_id')
                    ->label('Відповідальний')
                    ->relationship('user', 'name'),
                SelectFilter::make('status')
                    ->label('Статус')
                    ->options(Task::$statuses),
                SelectFilter::make('priority')
                    ->label('Пріоритет')
                    ->options(Task::$priorities),
                Filter::make('is_completed')
                    ->label('Завершено')
                    ->query(fn (Builder $query) => $query->where('is_completed', true)),
                Filter::make('created_at')
                    ->label('Створено від/до')
                    ->form([
                        DatePicker::make('created_from')->label('Від'),
                        DatePicker::make('created_to')->label('До'),
                    ])
                    ->query(fn (Builder $query, array $data) => ($data['created_from'] ? $query->whereDate('created_at', '>=', $data['created_from']) : $query)
                        ->when($data['created_to'], fn (Builder $q) => $q->whereDate('created_at', '<=', $data['created_to']))),
                Filter::make('deadline')
                    ->label('Дедлайн від/до')
                    ->form([
                        DatePicker::make('from')->label('Від'),
                        DatePicker::make('to')->label('До'),
                    ])
                    ->query(fn (Builder $query, array $data) => ($data['from'] ? $query->whereDate('deadline', '>=', $data['from']) : $query)
                        ->when($data['to'], fn (Builder $q) => $q->whereDate('deadline', '<=', $data['to']))),
                Filter::make('budget')
                    ->label('Бюджет від/до')
                    ->form([
                        TextInput::make('min')->label('Мін')->numeric(),
                        TextInput::make('max')->label('Макс')->numeric(),
                    ])
                    ->query(fn (Builder $query, array $data) => (filled($data['min']) ? $query->where('budget', '>=', $data['min']) : $query)
                        ->when(filled($data['max']), fn (Builder $q) => $q->where('budget', '<=', $data['max']))),
                Filter::make('progress')
                    ->label('Прогрес від/до')
                    ->form([
                        TextInput::make('min')->label('Мін')->numeric(),
                        TextInput::make('max')->label('Макс')->numeric(),
                    ])
                    ->query(fn (Builder $query, array $data) => (filled($data['min']) ? $query->where('progress', '>=', $data['min']) : $query)
                        ->when(filled($data['max']), fn (Builder $q) => $q->where('progress', '<=', $data['max']))),
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
