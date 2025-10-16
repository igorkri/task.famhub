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
            ]);
    }
}
