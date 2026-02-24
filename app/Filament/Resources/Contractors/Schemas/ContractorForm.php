<?php

namespace App\Filament\Resources\Contractors\Schemas;

use App\Models\Contractor;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ContractorForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Назва / Ім\'я підрядника')
                    ->required(),

                TextInput::make('phone')
                    ->label('Телефон')
                    ->live(onBlur: true)
                    ->rules(['regex:/^\+380\d{9}$/', 'required'])
                    ->required()
                    ->helperText('Номер телефону повинен бути у форматі +380XXXXXXXXX'),

                TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->live(onBlur: true)
                    ->nullable(),

                Select::make('type')
                    ->label('Тип підрядника')
                    ->options(Contractor::typeList())
                    ->required(),

                TextInput::make('full_name')
                    ->label('Повна назва / ПІБ')
                    ->required(),

                TextInput::make('in_the_person_of')->label('В особі кого діє підрядник')->required(),

                Toggle::make('is_active')
                    ->label('Активний')
                    ->required(),

                Toggle::make('my_company')
                    ->label('Моя компанія')
                    ->required()
                    ->live(),

                Section::make('Договір')
                    ->schema([
                        TextInput::make('dogovor.number')
                            ->label('Номер договору')
                            ->required(),
                        TextInput::make('dogovor.date')
                            ->label('Дата договору')
                            ->type('date')
                            ->required(),
                    ])
                    ->columns(2)
                    ->collapsible()
                    ->columnSpanFull()
                    ->hidden(fn ($get) => $get('my_company') === true),

                RichEditor::make('description')
                    ->label('Опис / Примітки')
                    ->columnSpanFull(),

                Section::make('Реквізити')
                    ->schema([
                        TextInput::make('requisites.name')
                            ->label('Назва ФОП / ТОВ')
                            ->required(),
                        TextInput::make('requisites.identification_code')
                            ->label('ЄДРПОУ / Ідентифікаційний номер')
                            ->required()
                            ->maxLength(20),

                        TextInput::make('requisites.legal_address')
                            ->label('Юридична адреса')
                            ->columnSpanFull(),
                        TextInput::make('requisites.physical_address')
                            ->label('Фізична адреса')
                            ->columnSpanFull(),
                        TextInput::make('requisites.bank_name')
                            ->label('Банк'),
                        TextInput::make('requisites.mfo')
                            ->label('МФО')
                            ->maxLength(10),
                        TextInput::make('requisites.iban')
                            ->label('IBAN')
                            ->columnSpanFull(),
                        TextInput::make('requisites.vat_certificate')
                            ->label('Свідоцтво ПДВ')
                            ->nullable(),
                        Textarea::make('requisites.taxation_note')
                            ->label('Примітка про оподаткування')
                            ->columnSpanFull()
                            ->rows(4),
                    ])
                    ->columns(2)
                        ->collapsible()
                        ->columnSpanFull(),

                FileUpload::make('dogovor_files')
                    ->label('Договір файли')
                    ->disk('public')
                    ->directory('contractor-dogovors')
                    ->visibility('public')
                    ->multiple()
                    ->columnSpanFull()
                    ->hidden(fn ($get) => $get('my_company') === true),
            ]);
    }
}
