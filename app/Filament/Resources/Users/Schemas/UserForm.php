<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Основна інформація')
                    ->schema([
                        TextInput::make('name')
                            ->label('Ім\'я')
                            ->required()
                            ->maxLength(255),
                        
                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        
                        TextInput::make('password')
                            ->label('Пароль')
                            ->password()
                            ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                            ->dehydrated(fn ($state) => filled($state))
                            ->required(fn (string $context): bool => $context === 'create')
                            ->maxLength(255)
                            ->helperText('Залиште порожнім, щоб не змінювати'),
                        
                        DateTimePicker::make('email_verified_at')
                            ->label('Email підтверджено'),
                    ])
                    ->columns(2),
                
                Section::make('Ролі та права')
                    ->schema([
                        Select::make('roles')
                            ->label('Ролі')
                            ->multiple()
                            ->relationship('roles', 'name')
                            ->preload()
                            ->searchable(),
                    ]),
                
                Section::make('Інтеграції')
                    ->schema([
                        TextInput::make('asana_gid')
                            ->label('Asana GID')
                            ->maxLength(255),
                    ])
                    ->collapsible()
                    ->collapsed(),

                Section::make('Налаштування цілей')
                    ->schema([
                        TextInput::make('hourly_rate')
                            ->label('Тариф за годину')
                            ->numeric()
                            ->default(400)
                            ->suffix('₴'),

                        TextInput::make('currency')
                            ->label('Валюта')
                            ->default('UAH')
                            ->maxLength(10),

                        TextInput::make('rate_coefficient')
                            ->label('Коефіцієнт')
                            ->numeric()
                            ->default(1.00)
                            ->step(0.01),

                        TextInput::make('monthly_hours_goal')
                            ->label('Місячна ціль годин')
                            ->numeric()
                            ->default(160)
                            ->suffix('год.'),

                        TextInput::make('monthly_earnings_goal')
                            ->label('Місячна ціль заробітку')
                            ->numeric()
                            ->default(64000)
                            ->suffix('₴'),

                        TextInput::make('weekly_tasks_goal')
                            ->label('Тижнева ціль завдань')
                            ->numeric()
                            ->default(10)
                            ->suffix('шт.'),
                    ])
                    ->columns(3)
                    ->collapsible(),
            ]);
    }
}
