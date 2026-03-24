<?php

namespace App\Filament\Develop\Resources\Develop\DevelopmentPrices\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class DevelopmentPriceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Section::make('Основна інформація')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('name')
                                    ->label('Назва послуги/роботи')
                                    ->required()->columnSpanFull(),
                                Textarea::make('description')
                                    ->label('Опис')->columnSpanFull(),
                            ]),
                    ])
                    ->columnSpan(2),
                Section::make('Ціни та години')
                    ->schema([
                        Grid::make(1)
                            ->schema([
                                TextInput::make('price_frontend')
                                    ->label('Ціна (Frontend)')
                                    ->required()
                                    ->numeric(),
                                TextInput::make('avg_hours_frontend')
                                    ->label('Середня кількість годин (Frontend)')
                                    ->numeric(),
                                TextInput::make('price_backend')
                                    ->label('Ціна (Backend)')
                                    ->required()
                                    ->numeric(),
                                TextInput::make('avg_hours_backend')
                                    ->label('Середня кількість годин (Backend)')
                                    ->numeric(),
                                Select::make('currency')
                                    ->label('Валюта')
                                    ->options(\App\Models\Develop\DevelopmentPrice::getCurrencyOptions())
                                    ->required()
                                    ->default('USD'),
                            ]),
                    ])
                    ->columnSpan(1),
            ]);
    }
}
