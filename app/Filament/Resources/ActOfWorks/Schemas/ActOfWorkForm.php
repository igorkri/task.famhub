<?php

namespace App\Filament\Resources\ActOfWorks\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ActOfWorkForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Основна інформація')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('number')
                                    ->label('Номер акту')
                                    ->default(fn () => (string) time())
                                    ->required()
                                    ->maxLength(255),

                                Select::make('status')
                                    ->label('Статус')
                                    ->options([
                                        'pending' => 'В очікуванні',
                                        'processing' => 'В обробці',
                                        'done' => 'Виконано',
                                        'cancelled' => 'Скасовано',
                                    ])
                                    ->default('pending')
                                    ->required(),

                                Select::make('user_id')
                                    ->label('Користувач')
                                    ->relationship('user', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required(),

                                DatePicker::make('date')
                                    ->label('Дата складання акту')
                                    ->default(now())
                                    ->required(),

                                Select::make('type')
                                    ->label('Тип запису')
                                    ->options([
                                        'act' => 'Акт',
                                        'income' => 'Надходження',
                                        'new_project' => 'Новий проєкт',
                                    ])
                                    ->default('act'),

                                Select::make('period_type')
                                    ->label('Період тип')
                                    ->options([
                                        'day' => 'День',
                                        'week' => 'Тиждень',
                                        'month' => 'Місяць',
                                        'quarter' => 'Квартал',
                                        'year' => 'Рік',
                                    ])
                                    ->reactive(),

                                TextInput::make('period_year')
                                    ->label('Рік періоду')
                                    ->numeric()
                                    ->default(date('Y'))
                                    ->maxLength(255),

                                TextInput::make('period_month')
                                    ->label('Місяць періоду')
                                    ->maxLength(255),
                            ]),

                        Grid::make(3)
                            ->schema([
                                TextInput::make('total_amount')
                                    ->label('Загальна сума')
                                    ->numeric()
                                    ->default(0)
                                    ->suffix('₴')
                                    ->step(0.01)
                                    ->required(),

                                TextInput::make('paid_amount')
                                    ->label('Сума, вже сплачена')
                                    ->numeric()
                                    ->default(0)
                                    ->suffix('₴')
                                    ->step(0.01)
                                    ->required(),

                                TextInput::make('sort')
                                    ->label('Порядок сортування')
                                    ->numeric()
                                    ->default(0),
                            ]),

                        Textarea::make('description')
                            ->label('Опис робіт')
                            ->rows(3)
                            ->columnSpanFull(),

                        FileUpload::make('file_excel')
                            ->label('Файл Excel')
                            ->acceptedFileTypes(['application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])
                            ->maxSize(10240)
                            ->columnSpanFull(),

                        Select::make('telegram_status')
                            ->label('Telegram статус')
                            ->options([
                                'pending' => 'В очікуванні',
                                'send' => 'Відправлено',
                                'error' => 'Помилка',
                            ])
                            ->default('pending'),
                    ]),

                Section::make('Деталі робіт')
                    ->schema([
                        Repeater::make('details')
                            ->relationship()
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        TextInput::make('project')
                                            ->label('Проект')
                                            ->maxLength(255),

                                        TextInput::make('task')
                                            ->label('Завдання')
                                            ->maxLength(255),

                                        TextInput::make('project_gid')
                                            ->label('ID проекту')
                                            ->maxLength(255),

                                        TextInput::make('task_gid')
                                            ->label('ID завдання')
                                            ->maxLength(255),

                                        TextInput::make('amount')
                                            ->label('Сума')
                                            ->numeric()
                                            ->default(0)
                                            ->suffix('₴')
                                            ->step(0.01)
                                            ->required(),

                                        TextInput::make('hours')
                                            ->label('Години')
                                            ->numeric()
                                            ->default(0)
                                            ->step(0.01)
                                            ->required(),

                                        TextInput::make('time_id')
                                            ->label('ID часу')
                                            ->numeric(),
                                    ]),

                                Textarea::make('description')
                                    ->label('Опис')
                                    ->rows(2)
                                    ->columnSpanFull(),
                            ])
                            ->columns(1)
                            ->defaultItems(0)
                            ->reorderable()
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['task'] ?? $state['project'] ?? null)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
