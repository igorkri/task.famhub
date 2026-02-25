<?php

namespace App\Filament\Resources\ContractorActOfCompletedWorks\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'Позиції акту';

    protected static ?string $recordTitleAttribute = 'service_description';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(3)
                    ->schema([
                        TextInput::make('sequence_number')
                            ->label('№ п/п')
                            ->required()
                            ->numeric()
                            ->default(fn ($livewire) => ($livewire->getOwnerRecord()->items()->max('sequence_number') ?? 0) + 1),

                        TextInput::make('unit')
                            ->label('Одиниця виміру')
                            ->required()
                            ->default('Послуга')
                            ->maxLength(255),
                    ]),

                Textarea::make('service_description')
                    ->label('Опис послуги/роботи')
                    ->required()
                    ->rows(3)
                    ->columnSpanFull(),

                Grid::make(3)
                    ->schema([
                        TextInput::make('quantity')
                            ->label('Кількість')
                            ->required()
                            ->numeric()
                            ->default(1)
                            ->step(0.01)
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                $unitPrice = $get('unit_price') ?? 0;
                                $amount = $state * $unitPrice;
                                $set('amount', number_format($amount, 2, '.', ''));
                            }),

                        TextInput::make('unit_price')
                            ->label('Ціна за одиницю')
                            ->required()
                            ->numeric()
                            ->prefix('₴')
                            ->default(0.0)
                            ->step(0.01)
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                $quantity = $get('quantity') ?? 1;
                                $amount = $quantity * $state;
                                $set('amount', number_format($amount, 2, '.', ''));
                            }),

                        TextInput::make('amount')
                            ->label('Сума по позиції')
                            ->required()
                            ->numeric()
                            ->prefix('₴')
                            ->default(0.0)
                            ->step(0.01)
                            ->disabled(),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('service_description')
            ->columns([
                TextColumn::make('sequence_number')
                    ->label('№ п/п')
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('service_description')
                    ->label('Опис послуги/роботи')
                    ->searchable()
                    ->wrap()
                    ->limit(50),

                TextColumn::make('unit')
                    ->label('Од.')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('quantity')
                    ->label('Кількість')
                    ->numeric(decimalPlaces: 2)
                    ->sortable()
                    ->alignEnd(),

                TextColumn::make('unit_price')
                    ->label('Ціна')
                    ->money('UAH')
                    ->sortable()
                    ->alignEnd(),

                TextColumn::make('amount')
                    ->label('Сума')
                    ->money('UAH')
                    ->sortable()
                    ->alignEnd()
                    ->summarize([
                        \Filament\Tables\Columns\Summarizers\Sum::make()
                            ->money('UAH')
                            ->label('Всього'),
                    ]),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('sequence_number', 'asc')
            ->paginated([10, 25, 50, 100]);
    }
}
