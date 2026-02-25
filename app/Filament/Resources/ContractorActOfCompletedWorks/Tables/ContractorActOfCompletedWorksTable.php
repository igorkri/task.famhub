<?php

namespace App\Filament\Resources\ContractorActOfCompletedWorks\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ContractorActOfCompletedWorksTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('number')
                    ->label('Номер акту')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('date')
                    ->label('Дата складання')
                    ->date('d.m.Y')
                    ->sortable(),

                TextColumn::make('contractor.name')
                    ->label('Підрядник')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('customer.name')
                    ->label('Замовник')
                    ->searchable()
                    ->sortable()
                    ->placeholder('—'),

                TextColumn::make('agreement_number')
                    ->label('Номер договору')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('total_amount')
                    ->label('Загальна сума')
                    ->money('UAH')
                    ->sortable()
                    ->alignEnd(),

                TextColumn::make('total_with_vat')
                    ->label('Сума з ПДВ')
                    ->money('UAH')
                    ->sortable()
                    ->alignEnd()
                    ->toggleable(),

                TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => \App\Models\ContractorActOfCompletedWork::$statusList[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'signed' => 'warning',
                        'paid' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),

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
                    ->options(\App\Models\ContractorActOfCompletedWork::$statusList),

                SelectFilter::make('contractor_id')
                    ->label('Підрядник')
                    ->relationship('contractor', 'name')
                    ->searchable()
                    ->preload(),
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
