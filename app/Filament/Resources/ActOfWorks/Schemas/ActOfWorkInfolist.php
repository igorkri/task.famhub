<?php

namespace App\Filament\Resources\ActOfWorks\Schemas;

use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ActOfWorkInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Основна інформація')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('number')
                                    ->label('Номер акту'),

                                TextEntry::make('status')
                                    ->label('Статус')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'pending' => 'warning',
                                        'processing' => 'info',
                                        'done' => 'success',
                                        'cancelled' => 'danger',
                                        default => 'gray',
                                    })
                                    ->formatStateUsing(fn (string $state): string => match ($state) {
                                        'pending' => 'В очікуванні',
                                        'processing' => 'В обробці',
                                        'done' => 'Виконано',
                                        'cancelled' => 'Скасовано',
                                        default => $state,
                                    }),

                                TextEntry::make('type')
                                    ->label('Тип')
                                    ->badge()
                                    ->formatStateUsing(fn (string $state): string => match ($state) {
                                        'act' => 'Акт',
                                        'income' => 'Надходження',
                                        'new_project' => 'Новий проєкт',
                                        default => $state,
                                    }),
                            ]),

                        Grid::make(3)
                            ->schema([
                                TextEntry::make('user.name')
                                    ->label('Користувач'),

                                TextEntry::make('date')
                                    ->label('Дата складання акту')
                                    ->date('d.m.Y'),

                                TextEntry::make('telegram_status')
                                    ->label('Telegram статус')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'pending' => 'warning',
                                        'send' => 'success',
                                        'error' => 'danger',
                                        default => 'gray',
                                    })
                                    ->formatStateUsing(fn (string $state): string => match ($state) {
                                        'pending' => 'В очікуванні',
                                        'send' => 'Відправлено',
                                        'error' => 'Помилка',
                                        default => $state,
                                    }),
                            ]),

                        Grid::make(3)
                            ->schema([
                                TextEntry::make('period_type')
                                    ->label('Період тип')
                                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                                        'day' => 'День',
                                        'week' => 'Тиждень',
                                        'month' => 'Місяць',
                                        'quarter' => 'Квартал',
                                        'year' => 'Рік',
                                        default => $state ?? '-',
                                    }),

                                TextEntry::make('period_year')
                                    ->label('Рік періоду'),

                                TextEntry::make('period_month')
                                    ->label('Місяць періоду'),
                            ]),

                        Grid::make(3)
                            ->schema([
                                TextEntry::make('total_amount')
                                    ->label('Загальна сума')
                                    ->money('UAH'),

                                TextEntry::make('paid_amount')
                                    ->label('Сума, вже сплачена')
                                    ->money('UAH'),

                                TextEntry::make('sort')
                                    ->label('Порядок сортування'),
                            ]),

                        TextEntry::make('description')
                            ->label('Опис робіт')
                            ->columnSpanFull(),

                        TextEntry::make('file_excel')
                            ->label('Файл Excel')
                            ->url(fn (?string $state): ?string => $state)
                            ->openUrlInNewTab()
                            ->columnSpanFull(),
                    ]),

                Section::make('Деталі робіт')
                    ->schema([
                        RepeatableEntry::make('details')
                            ->label('')
                            ->schema([
                                Grid::make(3)
                                    ->schema([
                                        TextEntry::make('project')
                                            ->label('Проект'),

                                        TextEntry::make('task')
                                            ->label('Завдання'),

                                        TextEntry::make('amount')
                                            ->label('Сума')
                                            ->money('UAH'),
                                    ]),

                                Grid::make(3)
                                    ->schema([
                                        TextEntry::make('hours')
                                            ->label('Години')
                                            ->suffix(' год'),

                                        TextEntry::make('project_gid')
                                            ->label('ID проекту'),

                                        TextEntry::make('task_gid')
                                            ->label('ID завдання'),
                                    ]),

                                TextEntry::make('description')
                                    ->label('Опис')
                                    ->columnSpanFull(),
                            ])
                            ->columnSpanFull(),
                    ]),

                Section::make('Системна інформація')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('created_at')
                                    ->label('Створено')
                                    ->dateTime('d.m.Y H:i'),

                                TextEntry::make('updated_at')
                                    ->label('Оновлено')
                                    ->dateTime('d.m.Y H:i'),
                            ]),
                    ])
                    ->collapsible(),
            ]);
    }
}
