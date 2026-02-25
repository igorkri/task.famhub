<?php

namespace App\Filament\Resources\ContractorActOfCompletedWorks\Schemas;

use App\Models\Contractor;
use App\Models\ContractorActOfCompletedWork;
use NumberToWords\NumberToWords;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class ContractorActOfCompletedWorkForm
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

                        DatePicker::make('date')
                            ->label('Дата складання акту')
                            ->required()
                            ->default(now()),

                        TextInput::make('place_of_compilation')
                            ->label('Місце складання')
                            ->default(Contractor::myCompany()->first()?->requisites['act_place'] ?? '')
                            ->maxLength(255),

                        Select::make('contractor_id')
                            ->label('Підрядник')
                            ->relationship(
                                name: 'contractor',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn ($query) => $query->where('my_company', true),
                            )
                            ->default(fn () => Contractor::myCompany()->first()?->id)
                            ->required()
                            ->disabled()
                            ->dehydrated()
                            ->helperText('У акті завжди виступаєте ви (моя компанія).'),

                        Select::make('status')
                            ->label('Статус')
                            ->options(ContractorActOfCompletedWork::$statusList)
                            ->required()
                            ->default(ContractorActOfCompletedWork::STATUS_DRAFT),
                    ]),

                Section::make('Договір')
                    ->columns(2)
                    ->schema([
                        TextInput::make('agreement_number')
                            ->label('Номер договору')
                            ->maxLength(255)
                            ->helperText('Заповнюється при виборі замовника.'),

                        DatePicker::make('agreement_date')
                            ->label('Дата договору')
                            ->helperText('Заповнюється при виборі замовника.'),
                    ])
                    ->collapsible(),

                Section::make('Дані замовника')
                    ->schema([
                        Select::make('customer_id')
                            ->label('Замовник')
                            ->relationship(
                                name: 'customer',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn ($query) => $query->where('my_company', false)->orderBy('name'),
                            )
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set): void {
                                if (!$state) {
                                    return;
                                }
                                $customer = Contractor::find($state);
                                if (!$customer) {
                                    return;
                                }
                                $req = $customer->requisites ?? [];
                                $name = $req['name'] ?? $customer->full_name ?? $customer->name ?? '';
                                $address = trim(implode("\n", array_filter([
                                    $req['legal_address'] ?? '',
                                    $req['physical_address'] ?? '',
                                ])));
                                $set('customer_data', [
                                    'name' => $name,
                                    'director' => $req['director'] ?? $customer->in_the_person_of ?? '',
                                    'identification_code' => $req['identification_code'] ?? '',
                                    'vat_certificate' => $req['vat_certificate'] ?? '',
                                    'individual_tax_number' => $req['individual_tax_number'] ?? $req['identification_code'] ?? '',
                                    'bank_name' => $req['bank_name'] ?? '',
                                    'mfo' => $req['mfo'] ?? '',
                                    'iban' => $req['iban'] ?? '',
                                    'address' => $address,
                                ]);

                                if ($customer->dogovor) {
                                    $set('agreement_number', $customer->dogovor['number'] ?? '');
                                    $set('agreement_date', isset($customer->dogovor['date'])
                                        ? \Carbon\Carbon::parse($customer->dogovor['date'])
                                        : null);
                                }
                            }),

                        TextInput::make('customer_data.name')
                            ->label('Назва компанії')
                            ->maxLength(255),

                        TextInput::make('customer_data.director')
                            ->label('Директор')
                            ->maxLength(255),

                        TextInput::make('customer_data.identification_code')
                            ->label('ЄДРПОУ')
                            ->maxLength(20),

                        TextInput::make('customer_data.vat_certificate')
                            ->label('Свідоцтво ПДВ')
                            ->maxLength(255),

                        TextInput::make('customer_data.individual_tax_number')
                            ->label('ІПН')
                            ->maxLength(20),

                        TextInput::make('customer_data.bank_name')
                            ->label('Банк')
                            ->maxLength(255),

                        TextInput::make('customer_data.mfo')
                            ->label('МФО')
                            ->maxLength(10),

                        TextInput::make('customer_data.iban')
                            ->label('IBAN')
                            ->maxLength(34)
                            ->columnSpanFull(),

                        Textarea::make('customer_data.address')
                            ->label('Адреса')
                            ->rows(2)
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Section::make('Фінансові підсумки')
                    ->columns(3)
                    ->schema([
                        TextInput::make('total_amount')
                            ->label('Загальна сума')
                            ->required()
                            ->numeric()
                            ->prefix('₴')
                            ->default(0.0)
                            ->step(0.01)
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set) {
                                $state = (float) ($state ?: 0);
                                $vatAmount = $state * 0.20; // 20% ПДВ
                                $set('vat_amount', number_format($vatAmount, 2, '.', ''));
                                $set('total_with_vat', number_format($state + $vatAmount, 2, '.', ''));
                                try {
                                    $amountInKopiyky = (int) round($state * 100);
                                    $set('total_amount_in_words', NumberToWords::transformCurrency('ua', $amountInKopiyky, 'UAH'));
                                } catch (\Throwable) {
                                    $set('total_amount_in_words', '');
                                }
                            }),

                        TextInput::make('vat_amount')
                            ->label('Сума ПДВ')
                            ->required()
                            ->numeric()
                            ->prefix('₴')
                            ->default(0.0)
                            ->step(0.01)
                            ->disabled(),

                        TextInput::make('total_with_vat')
                            ->label('Загальна сума з ПДВ')
                            ->required()
                            ->numeric()
                            ->prefix('₴')
                            ->default(0.0)
                            ->step(0.01)
                            ->disabled(),

                        TextInput::make('total_amount_in_words')
                            ->label('Сума прописом')
                            ->maxLength(255)
                            ->columnSpanFull()
                            ->helperText('Заповнюється автоматично при введенні загальної суми.'),
                    ]),

                Section::make('Додатково')
                    ->schema([
                        Textarea::make('description')
                            ->label('Опис / Примітки')
                            ->rows(4)
                            ->columnSpanFull(),

                        FileUpload::make('files')
                            ->label('Файли акту')
                            ->disk('public')
                            ->directory('contractor-acts-of-completed-works')
                            ->visibility('public')
                            ->multiple()
                            ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'])
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),
            ]);
    }
}
