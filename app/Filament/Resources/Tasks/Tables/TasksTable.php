<?php

namespace App\Filament\Resources\Tasks\Tables;

use App\Jobs\SyncProjectAsanaTasks;
use App\Models\Task;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\Layout\Panel;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
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
                Split::make([
                    TextColumn::make('project_id')
                        ->label('Проєкт')
                        ->getStateUsing(fn ($record) => $record->project ? $record->project->name : '-')
                        ->sortable(),
                    TextColumn::make('title')
                        ->label('Назва')
                        ->limit(50)
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
                ]),
                Panel::make([
                    Stack::make([
                        // Опис завдання
                        TextColumn::make('description')
                            ->label('📝 Опис завдання')
                            ->limit(200)
                            ->placeholder('Опис не вказано')
                            ->markdown()
                            ->extraAttributes([
                                'class' => 'bg-blue-50 dark:bg-blue-950/50 rounded-lg p-4 mb-4 border-l-4 border-blue-500',
                            ]),

                        // Розділ: Пріоритет та терміни
                        Stack::make([
                            TextColumn::make('section_priority')
                                ->label('⚡ ПРІОРИТЕТ ТА ТЕРМІНИ')
                                ->default('')
                                ->extraAttributes([
                                    'class' => 'text-xs font-bold uppercase text-gray-500 dark:text-gray-400 mb-2 mt-3',
                                ]),
                            Split::make([
                                TextColumn::make('priority')
                                    ->label('Пріоритет')
                                    ->formatStateUsing(fn ($record) => '🎯 Пріоритет: '.($record->priority ? Task::$priorities[$record->priority] : 'Не вказано'))
                                    ->badge()
                                    ->color(fn ($record) => match ($record->priority) {
                                        'high' => 'danger',
                                        'medium' => 'warning',
                                        'low' => 'success',
                                        default => 'gray',
                                    })
                                    ->grow(false),
                                TextColumn::make('deadline')
                                    ->label('Дедлайн')
                                    ->formatStateUsing(fn ($state) => $state ? '⏰ Дедлайн: '.\Carbon\Carbon::parse($state)->format('d.m.Y') : '⏰ Дедлайн: Не встановлено')
                                    ->badge()
                                    ->color(fn ($record) => $record->deadline && $record->deadline < now() ? 'danger' : 'gray')
                                    ->grow(false),
                                TextColumn::make('start_date')
                                    ->label('Початок')
                                    ->formatStateUsing(fn ($state) => $state ? '▶️ Початок: '.\Carbon\Carbon::parse($state)->format('d.m.Y H:i') : '▶️ Початок: Не вказано')
                                    ->badge()
                                    ->color('success')
                                    ->grow(false),
                                TextColumn::make('end_date')
                                    ->label('Завершення')
                                    ->formatStateUsing(fn ($state) => $state ? '🏁 Завершення: '.\Carbon\Carbon::parse($state)->format('d.m.Y H:i') : '🏁 Завершення: Не вказано')
                                    ->badge()
                                    ->color('info')
                                    ->grow(false),
                            ])->extraAttributes([
                                'class' => 'gap-4 bg-white dark:bg-gray-800 p-3 rounded-lg',
                            ]),
                        ]),

                        // Розділ: Бюджет та витрати
                        Stack::make([
                            TextColumn::make('section_budget')
                                ->label('💰 БЮДЖЕТ ТА ВИТРАТИ')
                                ->default('')
                                ->extraAttributes([
                                    'class' => 'text-xs font-bold uppercase text-gray-500 dark:text-gray-400 mb-2 mt-3',
                                ]),
                            Split::make([
                                TextColumn::make('budget')
                                    ->label('Бюджет')
                                    ->formatStateUsing(fn ($state) => $state ? '💵 Бюджет: '.number_format($state, 2).' грн' : '💵 Бюджет: Не вказано')
                                    ->badge()
                                    ->color('success')
                                    ->weight('bold')
                                    ->grow(false),
                                TextColumn::make('spent')
                                    ->label('Витрачено')
                                    ->formatStateUsing(fn ($state) => $state ? '💸 Витрачено: '.number_format($state, 2).' грн' : '💸 Витрачено: Не вказано')
                                    ->badge()
                                    ->color('warning')
                                    ->weight('bold')
                                    ->grow(false),
                                TextColumn::make('remaining')
                                    ->label('Залишок')
                                    ->getStateUsing(fn ($record) => $record->budget && $record->spent
                                        ? '💰 Залишок: '.number_format($record->budget - $record->spent, 2).' грн'
                                        : '💰 Залишок: Н/Д'
                                    )
                                    ->badge()
                                    ->color(function ($record) {
                                        if (! $record->budget || ! $record->spent) {
                                            return 'gray';
                                        }
                                        $remaining = $record->budget - $record->spent;

                                        return $remaining > 0 ? 'success' : 'danger';
                                    })
                                    ->weight('bold')
                                    ->grow(false),
                                TextColumn::make('progress')
                                    ->label('Прогрес')
                                    ->formatStateUsing(fn ($state) => $state ? '📊 Прогрес: '.$state.'%' : '📊 Прогрес: 0%')
                                    ->badge()
                                    ->color(fn ($state) => match (true) {
                                        $state >= 100 => 'success',
                                        $state >= 50 => 'warning',
                                        default => 'danger',
                                    })
                                    ->grow(false),
                            ])->extraAttributes([
                                'class' => 'gap-4 bg-white dark:bg-gray-800 p-3 rounded-lg',
                            ]),
                        ]),

                        // Розділ: Структура
                        Stack::make([
                            TextColumn::make('section_structure')
                                ->label('🗂️ СТРУКТУРА')
                                ->default('')
                                ->extraAttributes([
                                    'class' => 'text-xs font-bold uppercase text-gray-500 dark:text-gray-400 mb-2 mt-3',
                                ]),
                            Split::make([
                                TextColumn::make('section.name')
                                    ->label('Секція')
                                    ->formatStateUsing(fn ($state) => $state ? '📁 Секція: '.$state : '📁 Секція: Без секції')
                                    ->badge()
                                    ->color('purple')
                                    ->grow(false),
                                TextColumn::make('parent.title')
                                    ->label('Батьківське')
                                    ->formatStateUsing(fn ($state) => $state ? '⬆️ Батьківське: '.str($state)->limit(30) : '⬆️ Батьківське: Головне завдання')
                                    ->badge()
                                    ->color('info')
                                    ->grow(false),
                                TextColumn::make('children_count')
                                    ->label('Підзавдання')
                                    ->formatStateUsing(fn ($record) => '📋 Підзавдання: '.($record->children()->count() ?: '0'))
                                    ->badge()
                                    ->color(fn ($record) => $record->children()->count() > 0 ? 'success' : 'gray')
                                    ->grow(false),
                            ])->extraAttributes([
                                'class' => 'gap-4 bg-white dark:bg-gray-800 p-3 rounded-lg',
                            ]),
                        ]),

                        // Розділ: Asana та час
                        Stack::make([
                            TextColumn::make('section_asana')
                                ->label('🔗 ASANA ТА ЧАС')
                                ->default('')
                                ->extraAttributes([
                                    'class' => 'text-xs font-bold uppercase text-gray-500 dark:text-gray-400 mb-2 mt-3',
                                ]),
                            Split::make([
                                TextColumn::make('gid')
                                    ->label('Asana ID')
                                    ->formatStateUsing(fn ($state) => $state ? '🆔 Asana ID: '.$state : '🆔 Asana ID: Не синхронізовано')
                                    ->badge()
                                    ->color('gray')
                                    ->copyable()
                                    ->copyMessage('ID скопійовано!')
                                    ->grow(false),
                                TextColumn::make('permalink_url')
                                    ->label('Посилання')
                                    ->formatStateUsing(fn ($state) => $state ? '🔗 Посилання' : '🔗 Посилання: Відсутнє')
                                    ->url(fn ($record) => $record->permalink_url)
                                    ->openUrlInNewTab()
                                    ->badge()
                                    ->color('primary')
                                    ->grow(false),
                                TextColumn::make('times_count')
                                    ->label('Записи часу')
                                    ->formatStateUsing(fn ($record) => '⏱️ Записів часу: '.($record->times()->count() ?: '0'))
                                    ->badge()
                                    ->color(fn ($record) => $record->times()->count() > 0 ? 'success' : 'gray')
                                    ->grow(false),
                            ])->extraAttributes([
                                'class' => 'gap-4 bg-white dark:bg-gray-800 p-3 rounded-lg',
                            ]),
                        ]),
                    ])->extraAttributes([
                        'class' => 'gap-1',
                    ]),
                ])
                    ->collapsible()
                    ->collapsed()
                    ->extraAttributes([
                        'class' => 'bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-900 dark:to-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700',
                    ]),
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
                        // Попробуем прочитать фильтр project_id из table
                        $state = $table->getFilter('project_id')->getState();
                        $projectId = $state['values'][0] ?? null;

                        if (! $projectId) {
                            \Filament\Notifications\Notification::make()
                                ->danger()
                                ->title('Не вибрано проект')
                                ->body('Будь ласка, відфільтруйте завдання за проектом, щоб запустити синхронізацію проекту.')
                                ->send();

                            return;
                        }

                        // Достаём проект і диспатчим job
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
                            ->info()
                            ->title('Синхронізація проекту поставлена в чергу')
                            ->body('Синхронізація проекту поставлена в чергу і буде виконана найближчим часом.')
                            ->send();
                    })
                    ->requiresConfirmation(),
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
