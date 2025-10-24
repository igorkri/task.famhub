<?php

namespace App\Filament\Resources\ActOfWorks\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ActOfWorksTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('number')
                    ->label('Номер акту')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'processing' => 'info',
                        'done' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'В очікуванні',
                        'processing' => 'В обробці',
                        'done' => 'Виконано',
                        'cancelled' => 'Скасовано',
                        default => $state,
                    })
                    ->sortable(),

                TextColumn::make('user.name')
                    ->label('Користувач')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('date')
                    ->label('Дата')
                    ->date('d.m.Y')
                    ->sortable(),

                TextColumn::make('type')
                    ->label('Тип')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'act' => 'Акт',
                        'income' => 'Надходження',
                        'new_project' => 'Новий проєкт',
                        default => $state,
                    }),

                TextColumn::make('period_type')
                    ->label('Період')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'day' => 'День',
                        'week' => 'Тиждень',
                        'month' => 'Місяць',
                        'quarter' => 'Квартал',
                        'year' => 'Рік',
                        default => $state,
                    })
                    ->toggleable(),

                TextColumn::make('period_year')
                    ->label('Рік')
                    ->toggleable(),

                TextColumn::make('period_month')
                    ->label('Місяць')
                    ->toggleable(),

                TextColumn::make('total_amount')
                    ->label('Загальна сума')
                    ->money('UAH')
                    ->sortable(),

                TextColumn::make('paid_amount')
                    ->label('Сплачено')
                    ->money('UAH')
                    ->sortable(),

                TextColumn::make('details_count')
                    ->label('Кількість робіт')
                    ->counts('details')
                    ->sortable(),

                TextColumn::make('telegram_status')
                    ->label('Telegram')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'send' => 'success',
                        'error' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'В очікуванні',
                        'send' => 'Відправлено',
                        'error' => 'Помилка',
                        default => $state,
                    })
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('Створено')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('Оновлено')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Статус')
                    ->options([
                        'pending' => 'В очікуванні',
                        'processing' => 'В обробці',
                        'done' => 'Виконано',
                        'cancelled' => 'Скасовано',
                    ]),

                SelectFilter::make('type')
                    ->label('Тип')
                    ->options([
                        'act' => 'Акт',
                        'income' => 'Надходження',
                        'new_project' => 'Новий проєкт',
                    ]),

                SelectFilter::make('user_id')
                    ->label('Користувач')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('period_type')
                    ->label('Період тип')
                    ->options([
                        'day' => 'День',
                        'week' => 'Тиждень',
                        'month' => 'Місяць',
                        'quarter' => 'Квартал',
                        'year' => 'Рік',
                    ]),

                SelectFilter::make('telegram_status')
                    ->label('Telegram статус')
                    ->options([
                        'pending' => 'В очікуванні',
                        'send' => 'Відправлено',
                        'error' => 'Помилка',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('date', 'desc');
    }
}
