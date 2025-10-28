<?php

namespace App\Filament\Resources\Times\Tables;

use App\Exports\StyledTimesExport;
use App\Filament\Resources\Times\Actions\ExportToActOfWorkBulkAction;
use App\Models\Time;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
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

                TextColumn::make('title')
                    ->label('Завдання')
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
                    ->label('Статус')
                    ->getStateUsing(fn ($record) => $record->status ? Time::$statuses[$record->status] : '')
                    ->searchable(),

                TextColumn::make('report_status')
                    ->label('Статус акту')
                    ->getStateUsing(fn ($record) => $record->report_status ? Time::$reportStatuses[$record->report_status] : '')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                ToggleColumn::make('is_archived')
                    ->label('Архів'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                // фільтр за датою створення завдання
                Filter::make('task_created_at')
                    ->label('Фільтр за датою створення завдання')
                    ->form([
                        DatePicker::make('task_created_from')->label('Від'),
                        DatePicker::make('task_created_to')->label('До'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['task_created_from'],
                                fn (Builder $q) => $q->whereHas('task', fn (Builder $query) => $query->whereDate('created_at', '>=', $data['task_created_from']))
                            )
                            ->when(
                                $data['task_created_to'],
                                fn (Builder $q) => $q->whereHas('task', fn (Builder $query) => $query->whereDate('created_at', '<=', $data['task_created_to']))
                            );
                    }),
                // фільтр за статусом завдання
                SelectFilter::make('task_status')
                    ->label('Фільтр за статусом завдання')
                    ->options(\App\Models\Task::$statuses)
                    ->multiple()
                    ->query(function (Builder $query, array $data): Builder {
                        if (filled($data['values'])) {
                            return $query->whereHas('task', function (Builder $q) use ($data) {
                                $q->whereIn('status', $data['values']);
                            });
                        }

                        return $query;
                    }),
                // status filter
                SelectFilter::make('status')
                    ->options(Time::$statuses)
                    ->multiple()
                    ->label('Фільтр за статусом '),
                // archived filter
                SelectFilter::make('is_archived')
                    ->options([
                        0 => 'Не в архіві',
                        1 => 'В архіві',
                    ])->label('Фільтр за архівом'),
                // report_status filter
                SelectFilter::make('report_status')
                    ->options(Time::$reportStatuses)
                    ->multiple()
                    ->label('Фільтр за статусом акту'),
                // фильтр за проєктом через завдання
                SelectFilter::make('task.project_id')
                    ->label('Проєкт')
                    ->relationship('task.project', 'name')
                    ->multiple(),
            ])
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
            ]);
    }
}
