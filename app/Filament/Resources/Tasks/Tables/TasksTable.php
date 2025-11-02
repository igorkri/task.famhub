<?php

namespace App\Filament\Resources\Tasks\Tables;

use App\Jobs\SyncProjectAsanaTasks;
use App\Models\Task;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
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
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->title)
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
                    ->badge()
                    ->colors([
                        'primary' => fn ($state): bool => in_array($state, [Task::$statuses['new']]),
                        'warning' => fn ($state): bool => in_array($state, [Task::$statuses['in_progress']]),
                        'success' => fn ($state): bool => in_array($state, [Task::$statuses['completed']]),
                        'danger' => fn ($state): bool => in_array($state, [Task::$statuses['canceled']]),
                        'info' => fn ($state): bool => in_array($state, [Task::$statuses['needs_clarification']]),
                    ])
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
                    ->multiple()
                    ->label('Проєкт')
                    ->relationship('project', 'name'),
                SelectFilter::make('user_id')
                    ->label('Відповідальний')
                    ->multiple()
                    ->relationship('user', 'name'),
                SelectFilter::make('status')
                    ->label('Статус')
                    ->multiple()
                    ->options(Task::$statuses),
                SelectFilter::make('priority')
                    ->label('Пріоритет')
                    ->options(Task::$priorities),
                Filter::make('is_completed')
                    ->label('Завершено')
                    ->query(fn (Builder $query) => $query->where('is_completed', true)),
                //                Filter::make('created_at')
                //                    ->label('Створено від/до')
                //                    ->form([
                //                        DatePicker::make('created_from')->label('Від'),
                //                        DatePicker::make('created_to')->label('До'),
                //                    ])
                //                    ->query(fn (Builder $query, array $data) => ($data['created_from'] ? $query->whereDate('created_at', '>=', $data['created_from']) : $query)
                //                        ->when($data['created_to'], fn (Builder $q) => $q->whereDate('created_at', '<=', $data['created_to']))),
                //                Filter::make('deadline')
                //                    ->label('Дедлайн від/до')
                //                    ->form([
                //                        DatePicker::make('from')->label('Від'),
                //                        DatePicker::make('to')->label('До'),
                //                    ])
                //                    ->query(fn (Builder $query, array $data) => ($data['from'] ? $query->whereDate('deadline', '>=', $data['from']) : $query)
                //                        ->when($data['to'], fn (Builder $q) => $q->whereDate('deadline', '<=', $data['to']))),
                //                Filter::make('budget')
                //                    ->label('Бюджет від/до')
                //                    ->form([
                //                        TextInput::make('min')->label('Мін')->numeric(),
                //                        TextInput::make('max')->label('Макс')->numeric(),
                //                    ])
                //                    ->query(fn (Builder $query, array $data) => (filled($data['min']) ? $query->where('budget', '>=', $data['min']) : $query)
                //                        ->when(filled($data['max']), fn (Builder $q) => $q->where('budget', '<=', $data['max']))),
                //                Filter::make('progress')
                //                    ->label('Прогрес від/до')
                //                    ->form([
                //                        TextInput::make('min')->label('Мін')->numeric(),
                //                        TextInput::make('max')->label('Макс')->numeric(),
                //                    ])
                //                    ->query(fn (Builder $query, array $data) => (filled($data['min']) ? $query->where('progress', '>=', $data['min']) : $query)
                //                        ->when(filled($data['max']), fn (Builder $q) => $q->where('progress', '<=', $data['max']))),
            ])
            ->recordActions([
                EditAction::make(),
                //                Action::make('sync_asana')
                //                    ->label('Синхронізувати Asana')
                //                    ->icon('heroicon-o-arrow-path')
                //                    ->requiresConfirmation()
                //                    ->action(function (App\Models\Task $record) {
                //                        $task = $record;
                //
                //                        $gid = $task->gid ?? null;
                //                        if (! $gid) {
                //                            \Filament\Notifications\Notification::make()
                //                                ->danger()
                //                                ->title('Відсутній Asana task id')
                //                                ->body('Для цього завдання не налаштовано Asana GID.')
                //                                ->send();
                //
                //                            return;
                //                        }
                //
                //                        SyncTaskFromAsana::dispatch($task);
                //
                //                        \Filament\Notifications\Notification::make()
                //                            ->info()
                //                            ->title('Синхронізація поставлена в чергу')
                //                            ->body('Синхронізація завдання поставлена в чергу і буде виконана найближчим часом.')
                //                            ->send();
                //                    }),
            ])
            ->toolbarActions([
                Action::make('sync_project_asana')
                    ->label('Синхронізувати проект Asana')
                    ->icon('heroicon-o-arrow-path')
                    ->action(function () use ($table) {
                        $state = $table->getFilter('project_id')->getState();
                        $projectId = $state['values'][0] ?? null;

                        if (! $projectId) {
                            // Якщо проект не вибрано, синхронізуємо всі проекти
                            $projects = \App\Models\Project::all();

                            if ($projects->isEmpty()) {
                                \Filament\Notifications\Notification::make()
                                    ->warning()
                                    ->title('Немає проектів')
                                    ->body('Немає проектів для синхронізації.')
                                    ->send();

                                return;
                            }

                            foreach ($projects as $project) {
                                SyncProjectAsanaTasks::dispatch($project);
                            }

                            \Filament\Notifications\Notification::make()
                                ->success()
                                ->title('Синхронізація всіх проектів поставлена в чергу')
                                ->body("Синхронізація {$projects->count()} проектів поставлена в чергу і буде виконана найближчим часом.")
                                ->send();

                            return;
                        }

                        // Синхронізуємо один вибраний проект
                        $project = \App\Models\Project::find($projectId);
                        if (! $project instanceof \App\Models\Project) {
                            \Filament\Notifications\Notification::make()
                                ->danger()
                                ->title('Проект не знайдено')
                                ->body('Вибраний проект не знайдено.')
                                ->send();

                            return;
                        }

                        SyncProjectAsanaTasks::dispatch($project);

                        \Filament\Notifications\Notification::make()
                            ->success()
                            ->title('Синхронізація проекту поставлена в чергу')
                            ->body("Синхронізація проекту \"{$project->name}\" поставлена в чергу і буде виконана найближчим часом.")
                            ->send();
                    })
                    ->requiresConfirmation(),
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
