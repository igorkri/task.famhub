<?php

namespace App\Filament\Resources\Times\Tables;

use App\Exports\StyledTimesExport;
use App\Filament\Resources\Times\Actions\ExportToActOfWorkBulkAction;
use App\Models\Time;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class TimesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                //                TextColumn::make('task_id')
                //                    ->numeric()
                //                    ->sortable(),

                TextColumn::make('user.name')
                    ->label('Виконавець')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('task.project.name')
                    ->label('Проєкт')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                // статус таска
                TextColumn::make('task.status')
                    ->label('Статус завдання')
                    ->getStateUsing(fn ($record) => $record->task ? \App\Models\Task::$statuses[$record->task->status] : '')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('title')
                    ->label('Завдання трекінгу')
                    ->limit(50)
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('description')
                    ->label('Опис трекінгу')
                    ->limit(50)
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('task.title')
                    ->label('Назва завдання')
                    ->limit(50)
                    ->searchable(),

                TextColumn::make('duration')
                    ->label('Годин')
                    ->getStateUsing(fn ($record) => $record->duration / 3600)
                    ->formatStateUsing(fn ($state) => number_format($state, 2))
                    ->numeric()
                    ->searchable()
                    ->sortable(),

                TextColumn::make('coefficient')
                    ->label('Коефіцієнт')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('calculated_amount')
                    ->label('Сума, грн')
                    ->formatStateUsing(fn ($state) => number_format($state, 2))
                    ->numeric()
                    ->sortable(query: fn ($query, $direction) => $query->orderByRaw('(duration / 3600 * coefficient * '.\App\Models\Time::PRICE.') '.$direction))
                    ->summarize(Sum::make()->label('Загальна сума'))
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('status')
                    ->label('Статус трекінгу')
                    ->getStateUsing(fn ($record) => $record->status ? Time::$statuses[$record->status] : '')
                    ->searchable()->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('report_status')
                    ->label('Статус акту')
                    ->getStateUsing(fn ($record) => $record->report_status ? Time::$reportStatuses[$record->report_status] : '')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                ToggleColumn::make('is_archived')
                    ->label('Архів')->toggleable(isToggledHiddenByDefault: true),
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
                // Дата створення таска
                TextColumn::make('task.created_at')
                    ->label('Дата створення завдання')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filter::make('filters')
                    ->form([
                        Grid::make(2)
                            ->schema([
                                Section::make('Фільтри по завданню')
                                    ->description('Фільтрація за параметрами завдань')
                                    ->icon('heroicon-o-clipboard-document-list')
                                    ->schema([
                                        DatePicker::make('task_created_from')
                                            ->label('Дата створення від')
                                            ->native(false),
                                        DatePicker::make('task_created_to')
                                            ->label('Дата створення до')
                                            ->native(false),
                                        \Filament\Forms\Components\Select::make('task_status')
                                            ->label('Статус завдання')
                                            ->options(\App\Models\Task::$statuses)
                                            ->multiple()
                                            ->searchable()
                                            ->preload(),
                                        \Filament\Forms\Components\Select::make('task_project_id')
                                            ->label('Проєкт')
                                            ->relationship('task.project', 'name')
                                            ->multiple()
                                            ->searchable()
                                            ->preload(),
                                    ])
                                    ->columns(1)
                                    ->collapsible(),

                                Section::make('Фільтри по трекінгу')
                                    ->description('Фільтрація за параметрами трекінгу часу')
                                    ->icon('heroicon-o-clock')
                                    ->schema([
                                        \Filament\Forms\Components\Select::make('time_status')
                                            ->label('Статус трекінгу')
                                            ->options(Time::$statuses)
                                            ->multiple()
                                            ->searchable(),
                                        \Filament\Forms\Components\Select::make('time_archived')
                                            ->label('Архів')
                                            ->options([
                                                0 => 'Не в архіві',
                                                1 => 'В архіві',
                                            ])
                                            ->placeholder('Всі'),
                                        \Filament\Forms\Components\Select::make('time_report_status')
                                            ->label('Статус акту')
                                            ->options(Time::$reportStatuses)
                                            ->multiple()
                                            ->searchable(),
                                    ])
                                    ->columns(1)
                                    ->collapsible(),
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            // Фільтри по завданню
                            ->when(
                                $data['task_created_from'] ?? null,
                                function (Builder $q) use ($data) {
                                    return $q->whereHas('task', function (Builder $query) use ($data) {
                                        $query->whereDate('created_at', '>=', $data['task_created_from']);
                                    });
                                }
                            )
                            ->when(
                                $data['task_created_to'] ?? null,
                                function (Builder $q) use ($data) {
                                    return $q->whereHas('task', function (Builder $query) use ($data) {
                                        $query->whereDate('created_at', '<=', $data['task_created_to']);
                                    });
                                }
                            )
                            ->when(
                                filled($data['task_status'] ?? null),
                                function (Builder $q) use ($data) {
                                    return $q->whereHas('task', function (Builder $query) use ($data) {
                                        $query->whereIn('status', $data['task_status']);
                                    });
                                }
                            )
                            ->when(
                                filled($data['task_project_id'] ?? null),
                                function (Builder $q) use ($data) {
                                    return $q->whereHas('task', function (Builder $query) use ($data) {
                                        $query->whereIn('project_id', $data['task_project_id']);
                                    });
                                }
                            )
                            // Фільтри по трекінгу
                            ->when(
                                filled($data['time_status'] ?? null),
                                fn (Builder $q) => $q->whereIn('status', $data['time_status'])
                            )
                            ->when(
                                isset($data['time_archived']),
                                fn (Builder $q) => $q->where('is_archived', $data['time_archived'])
                            )
                            ->when(
                                filled($data['time_report_status'] ?? null),
                                fn (Builder $q) => $q->whereIn('report_status', $data['time_report_status'])
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if ($data['task_created_from'] ?? null) {
                            $indicators['task_created_from'] = 'Завдання від: '.\Carbon\Carbon::parse($data['task_created_from'])->format('d.m.Y');
                        }
                        if ($data['task_created_to'] ?? null) {
                            $indicators['task_created_to'] = 'Завдання до: '.\Carbon\Carbon::parse($data['task_created_to'])->format('d.m.Y');
                        }
                        if ($data['task_status'] ?? null) {
                            $statuses = collect($data['task_status'])->map(fn ($status) => \App\Models\Task::$statuses[$status] ?? $status)->join(', ');
                            $indicators['task_status'] = 'Статус завдання: '.$statuses;
                        }
                        if ($data['task_project_id'] ?? null) {
                            $projects = \App\Models\Project::whereIn('id', $data['task_project_id'])->pluck('name')->join(', ');
                            $indicators['task_project_id'] = 'Проєкт: '.$projects;
                        }
                        if ($data['time_status'] ?? null) {
                            $statuses = collect($data['time_status'])->map(fn ($status) => Time::$statuses[$status] ?? $status)->join(', ');
                            $indicators['time_status'] = 'Статус трекінгу: '.$statuses;
                        }
                        if (isset($data['time_archived'])) {
                            $indicators['time_archived'] = 'Архів: '.($data['time_archived'] ? 'В архіві' : 'Не в архіві');
                        }
                        if ($data['time_report_status'] ?? null) {
                            $statuses = collect($data['time_report_status'])->map(fn ($status) => Time::$reportStatuses[$status] ?? $status)->join(', ');
                            $indicators['time_report_status'] = 'Статус акту: '.$statuses;
                        }

                        return $indicators;
                    }),
            ], layout: \Filament\Tables\Enums\FiltersLayout::Modal)
            ->filtersFormWidth('5xl')
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    ExportToActOfWorkBulkAction::make(),
                    ExportBulkAction::make()
                        ->label('Експорт у Excel в файл')
                        ->icon('heroicon-o-document-text')
                        ->color('success')
                        ->requiresConfirmation()
                        ->exports([
                            StyledTimesExport::make(),
                        ]),
                    DeleteBulkAction::make(),
                ]),
            ])
            ->paginated([10, 25, 50, 100, 250, 500])
            ->defaultPaginationPageOption(25);
    }
}
