<?php

namespace App\Filament\Resources\Times\Schemas;

use App\Models\Task;
use App\Models\Time;
use App\Models\User;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TimeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('task_id')
                    ->label('Завдання')
                    ->required()
                    ->options(Task::with('project')->get()->sortByDesc('title')->mapWithKeys(fn ($task) => [$task->id => $task->project->name.' - '.$task->title])->toArray())
                    ->searchable()
                    ->placeholder('Виберіть завдання...'),
                Select::make('user_id')->label('Виконавець')->required()
                    ->options(User::all()->sortByDesc('name')->pluck('name', 'id')->toArray()),
                TextInput::make('title')->label('Назва')->required()->maxLength(255),
                Textarea::make('description')
                    ->label('Опис')
                    ->columnSpanFull(),
                TextInput::make('coefficient')
                    ->label('Коефіцієнт')
                    ->required()
                    ->default(1.2)
                    ->numeric(),
                //                    ->options(Time::$coefficients),
                TimePicker::make('duration')
                    ->label('Тривалість (год:хв:сек)')
                    ->required()
                    ->default(0),
                Select::make('status')
                    ->label('Статус')
                    ->options(Time::$statuses)
                    ->required()
                    ->default('in_progress'),
                Select::make('report_status')
                    ->label('Статус акту')
                    ->options(Time::$reportStatuses)
                    ->required()
                    ->default('not_submitted'),
                Toggle::make('is_archived')
                    ->label('Архівний')
                    ->required(),

                Section::make('Total')
                    ->columns(3)
                    ->schema([
                        Placeholder::make('calculated_amount')
                            ->label('Сума, грн')
                            ->content(fn ($record) => $record
                                ? number_format($record->getCalculatedAmountAttribute(), 2, '.', ',')
                                : '0.00'),
                        Placeholder::make('created_at')
                            ->label('Створено')
                            ->content(fn ($record) => $record
                                ? $record->created_at->format('d.m.Y H:i')
                                : '-'),
                        Placeholder::make('updated_at')
                            ->label('Оновлено')
                            ->content(fn ($record) => $record
                                ? $record->updated_at->format('d.m.Y H:i')
                                : '-'),
                    ])->columnSpanFull(),
            ]);

    }
}
