<?php

namespace App\Filament\Resources\Tasks\Schemas;

use App\Models\Task;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Flex;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TaskForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Flex::make([
                    // Левая часть: основное
                    Section::make('Основне')
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
//                            TextInput::make('parent_id')
//                                ->label('Батьківське завдання')
//                                ->numeric(),
                            Toggle::make('is_completed')
                                ->label('Завершено')
                                ->default(false)
                                ->inline(false),

                            Radio::make('status')
                                ->label('Статус')
                                ->options(Task::$statuses)
                                ->required()
                                ->default('new'),
                            Radio::make('priority')
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
                        ->grow(false)   // не росте
                        ->maxWidth('300px'), // або задаєш жорстку межу
                ])->from('md'),
            ])
            ->columns(1);


    }
}
