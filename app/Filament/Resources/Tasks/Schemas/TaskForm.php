<?php

namespace App\Filament\Resources\Tasks\Schemas;

use App\Models\Task;
use App\Models\Time;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ViewField;
use Filament\Schemas\Components\Flex;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use Filament\Tables\Components\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Actions\EditAction;
use Filament\Forms\Components\Repeater;

class TaskForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Tabs')
                ->tabs([
                    Tabs\Tab::make('Основне')
                        ->icon('heroicon-o-document-text')
                        ->schema([
                            self::mainSection(),
                        ]),
                    Tabs\Tab::make('Таймер')
                        ->icon('heroicon-o-clock')
                        ->badge(fn ($record) => optional($record)?->times()->count() ?? 0)
                        ->schema([
                            self::timerSection(),
                        ]),
                ])
                ->persistTabInQueryString()
                ->columnSpanFull(),
            ])
            ->columns(1);
    }

    private static function mainSection()
    {
        return Flex::make([
            // Левая часть: основное
            Section::make('Опис та назва')
                ->schema([
                    TextInput::make('title')
                        ->label('Назва')
                        ->required(),

                    MarkdownEditor::make('description')
                        ->label('Опис')
                        ->columnSpanFull(),
                ])
                ->grow(1), // займає всю доступну ширину

            // Правая часть: метаданные
            Section::make('Додатково')
                ->schema([
                    ViewField::make('timer')
                        ->view('components.task-timer')
                        ->viewData([
                            'task' => fn ($record) => $record,
                            'user' => fn () => auth()->user(),
                            'time_id' => fn ($record) => optional($record)
                                ?->times()
                                ->where('user_id', auth()->id())
                                ->value('id'),
                        ])
                        ->columnSpanFull(),

                    Toggle::make('is_completed')
                        ->label('Завершено')
                        ->default(false)
                        ->inline(false),

                    Section::make('Робочі параметри') // группа, которую можно свернуть
                    ->schema([
                        Select::make('status')
                            ->label('Статус')
                            ->options(Task::$statuses)
                            ->required()
                            ->default(Task::STATUS_NEW),

                        Select::make('priority')
                            ->label('Пріоритет')
                            ->options(Task::$priorities)
                            ->nullable(),

                        Select::make('project_id')
                            ->label('Проєкт')
                            ->relationship('project', 'name')
                            ->required(),

                        Select::make('user_id')
                            ->label('Виконавець')
                            ->relationship('user', 'name'),
                    ])
                        ->collapsible() // делаем секцию сворачиваемой
                        ->collapsed(false),  // по умолчанию скрыта


                    Section::make('Час і бюджет')
                        ->schema([
                            TextInput::make('budget')
                                ->label('Бюджет (години)')
                                ->numeric(),

                            TextInput::make('spent')
                                ->label('Витрачено (годин)')
                                ->numeric()
                                ->required()
                                ->default(0),

                            DateTimePicker::make('start_date')
                                ->label('Початок'),

                            DateTimePicker::make('end_date')
                                ->label('Завершення'),

                            DatePicker::make('deadline')
                                ->label('Дедлайн'),

                            TextInput::make('progress')
                                ->label('Прогрес (%)')
                                ->numeric()
                                ->required()
                                ->default(0),
                        ])
                        ->collapsible()
                        ->collapsed(), // можно свернуть по умолчанию
                ])
                ->grow(false)
                ->maxWidth('300px')// або задаєш жорстку межу
        ])->from('md');
    }

    private static function timerSection()
    {
        return Section::make('Таймер')
            ->schema([
                ViewField::make('total_time')
                    ->view('components.total-time')
                    ->viewData(fn ($record) => [
                        'times' => optional($record)?->times ?? collect(),
                    ])
                    ->columnSpanFull(),

                Repeater::make('times')
                    ->relationship('times')
                    ->label('Тайминги')
                    ->schema([

                        TimePicker::make('duration')
                            ->label('Час')
                            ->seconds(true)
                            ->required()
                            ->dehydrateStateUsing(fn($state) => $state) // чтобы не сохранять duration_for_form напрямую
                            ->afterStateHydrated(function ($component, $state) {
                                $component->state($state ?? '00:00:00');
                            }),

                        // user_id
                        Select::make('user_id')
                            ->label('Користувач')
                            ->relationship('user', 'name')
                            ->required(),
                        // task_id aвтоматично ставляється

                        Select::make('coefficient')
                            ->label('Коефіцієнт')
                            ->options(collect(Time::$coefficients)->mapWithKeys(fn($v, $k) => [(string)$k => $v])->toArray())
                            ->required(),
                        Select::make('status')
                            ->label('Статус')
                            ->options(Time::$statuses)
                            ->required(),
                        TextInput::make('title')
                            ->label('Заголовок')
                            ->required()->columnSpanFull(),
                        Textarea::make('description')
                            ->label('Опис')->columnSpanFull(),
                    ])
                    ->defaultItems(0)
                    ->addActionLabel('Добавить тайминг')
                    // сворачиваемый
                    ->collapsible()
                    // по умолчанию свернутый
                    ->collapsed()
                    // делаем название из поля title
                    ->itemLabel(fn ($state) => $state['title'] .' Час: '. $state['duration'] ?? 'Новий тайминг')
                    ->columns(4),
            ])
            ->columnSpanFull();
    }
}
