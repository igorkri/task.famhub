<?php

namespace App\Filament\Resources\Times\Schemas;

use App\Models\Task;
use App\Models\Time;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
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
                    ->options(function () {
                        return \App\Models\User::usersList();
                    }),
                TextInput::make('title')->label('Назва')->required()->maxLength(255)->columnSpanFull(),
                Textarea::make('description')
                    ->label('Опис')
                    ->columnSpanFull(),
                TextInput::make('coefficient')
                    ->label('Коефіцієнт')
                    ->required()
                    ->default(1.2)
                    ->numeric(),
                //                    ->options(Time::$coefficients),
                TextInput::make('duration')
                    ->label('⏰ Час')
                    ->placeholder('200:02:12')
                    ->helperText('Формат: ГГ:ХХ:СС. Години можуть бути більше 24.')
                    ->required()
                    ->rule('regex:/^\d+:[0-5]\d:[0-5]\d$/')
                    ->validationMessages([
                        'regex' => 'Використовуйте формат ГГ:ХХ:СС, наприклад 200:02:12.',
                    ])
                    ->dehydrateStateUsing(fn ($state) => trim((string) $state))
                    ->afterStateHydrated(function ($component, $state) {
                        if (is_numeric($state)) {
                            $seconds = (int) $state;
                            $hours = str_pad((string) floor($seconds / 3600), 2, '0', STR_PAD_LEFT);
                            $minutes = str_pad((string) floor(($seconds % 3600) / 60), 2, '0', STR_PAD_LEFT);
                            $remainingSeconds = str_pad((string) ($seconds % 60), 2, '0', STR_PAD_LEFT);

                            $component->state("{$hours}:{$minutes}:{$remainingSeconds}");

                            return;
                        }

                        $component->state($state ?: '00:00:00');
                    })
                    ->grow(false),
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
