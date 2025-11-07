<?php

namespace App\Filament\Resources\ActOfWorks\Schemas;

use App\Models\ActOfWork;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;

class ActOfWorkForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([


                Section::make('Основна інформація')
                    ->columns(2)
                    ->schema([
                        TextInput::make('number')
                            ->label('Номер акту')
                            ->required()
                            ->maxLength(255),

                        Select::make('type')
                            ->label('Тип акту')
                            ->options(ActOfWork::$type)
                            ->required()
                            ->default(ActOfWork::TYPE_ACT),

                        Select::make('status')
                            ->label('Статус')
                            ->options(ActOfWork::$statusList)
                            ->required()
                            ->default(ActOfWork::STATUS_PENDING),

                        DatePicker::make('date')
                            ->label('Дата складання акту')
                            ->required()
                            ->default(now()),

                        Select::make('user_id')
                            ->label('Користувач')
                            ->options(function () {
                                return \App\Models\User::usersList();
                            })
                            ->searchable()
                            ->preload()
                            ->required(),
                    ]),

                Section::make('Період')
                    ->columns(3)
                    ->schema([
                        Select::make('period_type')
                            ->label('Тип періоду')
                            ->options(ActOfWork::$periodTypeList)
                            ->reactive(),

                        Select::make('period_month')
                            ->label('Місяць періоду')
                            ->options(ActOfWork::$monthsList),

                        Select::make('period_year')
                            ->label('Рік періоду')
                            ->options(ActOfWork::$yearsList),
                    ]),

                Section::make('Опис та суми')
                    ->columns(2)
                    ->schema([
                        Textarea::make('description')
                            ->label('Опис робіт')
                            ->rows(4)
                            ->columnSpanFull(),

                        TextInput::make('total_amount')
                            ->label('Загальна сума')
                            ->required()
                            ->numeric()
                            ->prefix('₴')
                            ->default(0.0)
                            ->step(0.01),

                        TextInput::make('paid_amount')
                            ->label('Сума, вже сплачена')
                            ->required()
                            ->numeric()
                            ->prefix('₴')
                            ->default(0.0)
                            ->step(0.01),
                    ]),

                                Section::make('Додаткові дані')
                                    ->columns(1)
                                    ->schema([
                                        FileUpload::make('file_excel')
                                            ->label('Файл Excel')
                                            ->acceptedFileTypes(['application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/excel'])
                                            ->disk('public')
                                            ->directory('act-of-works')
                                            ->visibility('private'),

                                        Select::make('telegram_status')
                                            ->label('Статус Telegram')
                                            ->options(ActOfWork::$telegramStatusList)
                                            ->required()
                                            ->default(ActOfWork::TELEGRAM_STATUS_PENDING),


                                    ]),
            ]);
    }
}
