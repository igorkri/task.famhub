<?php

namespace App\Filament\Resources\Times\Tables;

use App\Models\Time;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

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
                // status filter
                \Filament\Tables\Filters\SelectFilter::make('status')
                    ->options(Time::$statuses)
                    ->multiple()
                    ->label('Фільтр за статусом'),
                // archived filter
                \Filament\Tables\Filters\SelectFilter::make('is_archived')
                    ->options([
                        0 => 'Не в архіві',
                        1 => 'В архіві',
                    ])->label('Фільтр за архівом'),
                // report_status filter
                \Filament\Tables\Filters\SelectFilter::make('report_status')
                    ->options(Time::$reportStatuses)
                    ->multiple()
                    ->label('Фільтр за статусом акту'),
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
