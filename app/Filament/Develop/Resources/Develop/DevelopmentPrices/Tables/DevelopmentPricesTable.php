<?php

namespace App\Filament\Develop\Resources\Develop\DevelopmentPrices\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DevelopmentPricesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->striped()
            ->paginationPageOptions([10, 25, 50, 100, 250, 500])
        
            ->columns([
                TextColumn::make('name')
                    ->label('Назва послуги/роботи')
                    ->searchable(),
                TextColumn::make('price_frontend')
                    ->label('Ціна (F)')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('avg_hours_frontend')
                    ->label('AVG Hours (F)')
                    ->numeric()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                TextColumn::make('price_backend')
                    ->label('Ціна (B)')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('avg_hours_backend')
                    ->label('AVG Hours (B)')
                    ->numeric()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                TextColumn::make('currency')
                    ->label('Валюта')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
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
                // Додайте фільтри за потребою
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
