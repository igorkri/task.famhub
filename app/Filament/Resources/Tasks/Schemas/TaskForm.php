<?php

namespace App\Filament\Resources\Tasks\Schemas;

use App\Models\Task;
use App\Models\Time;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ViewField;
use Filament\Schemas\Components\Flex;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;

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
                            ])
                            ->visible(fn ($record) => $record !== null), // показываем только для существующих записей
                        Tabs\Tab::make('Коментарі')
                            ->icon('heroicon-o-chat-bubble-left-right')
                            ->badge(fn ($record) => optional($record)?->comments()->count() ?? 0)
                            ->schema([
                                self::commentsSection(),
                            ])
                            ->visible(fn ($record) => $record !== null), // показываем только для существующих записей
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
                ->grow(1), // занимает всю доступную ширину

            // Правая часть: метаданные
            Section::make('Додатково')
                ->schema([
                    ViewField::make('timer')
                        ->view('components.livewire-timer-wrapper')
                        ->viewData(fn ($record) => [
                            'task' => $record,
                        ])
                        ->visible(fn ($record) => $record !== null),

                    Toggle::make('is_completed')
                        ->label('Завершено')
                        ->default(false)
                        ->inline(false),

                    TextInput::make('asana_link')
                        ->label('Посилання на Asana')
                        ->url()
                        ->prefix('🔗')
                        ->formatStateUsing(fn ($record) => $record?->gid
                            ? "https://app.asana.com/0/0/{$record->gid}/f"
                            : null)
                        ->disabled()
                        ->dehydrated(false)
                        ->visible(fn ($record) => $record?->gid !== null)
                        ->hint(fn ($record) => $record?->gid
                            ? new \Illuminate\Support\HtmlString(
                                '<a href="https://app.asana.com/0/0/'.$record->gid.'/f" target="_blank" class="text-primary-600 hover:underline flex items-center gap-1">
                                    Відкрити в Asana
                                </a>'
                            )
                            : null
                        ),

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
                                ->label('Проект')
                                ->relationship('project', 'name')
                                ->required(),

                            Select::make('user_id')
                                ->label('Виконавець')
                                ->relationship('user', 'name'),
                        ])
                        ->collapsible() // делаем секцию сворачиваемой
                        ->collapsed(false),  // по умолчанию открыта

                    Section::make('Час і бюджет')
                        ->schema([
                            TextInput::make('budget')
                                ->label('Бюджет (години)')
                                ->numeric(),

                            TextInput::make('spent')
                                ->label('Витрачено (хвилини)')
                                ->numeric()
                                ->required()
                                ->default(0)
                                ->suffixAction(
                                    Action::make('calculate_spent')
                                        ->icon('heroicon-o-calculator')
                                        ->tooltip('Порахувати з таймера')
                                        ->action(function ($set, $get, $record) {
                                            if (! $record) {
                                                return;
                                            }

                                            $totalSeconds = \App\Models\Time::where('task_id', $record->id)
                                                ->sum('duration');

                                            $totalMinutes = round($totalSeconds / 60);

                                            $set('spent', $totalMinutes);

                                            $hours = floor($totalMinutes / 60);
                                            $minutes = $totalMinutes % 60;

                                            \Filament\Notifications\Notification::make()
                                                ->title('Підраховано')
                                                ->body("Загальний час: {$totalMinutes} хвилин ({$hours} год {$minutes} хв)")
                                                ->success()
                                                ->send();
                                        })
                                        ->visible(fn ($record) => $record !== null)
                                ),

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
                ->maxWidth('300px'), // или задаем жесткую ширину
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
                    ->label('Записи часу')
                    ->schema([

                        TimePicker::make('duration')
                            ->label('Час')
                            ->seconds(true)
                            ->required()
                            ->dehydrateStateUsing(fn ($state) => $state) // чтобы не сохранять duration_for_form напрямую
                            ->afterStateHydrated(function ($component, $state) {
                                $component->state($state ?? '00:00:00');
                            }),

                        // user_id
                        Select::make('user_id')
                            ->label('Користувач')
                            ->default(auth()->id())
                            ->relationship('user', 'name')
                            ->required(),
                        // task_id автоматически ставится

                        TextInput::make('coefficient')
                            ->label('Коефіцієнт')
                            ->default(Time::COEFFICIENT_STANDARD)
//                            ->options(collect(Time::$coefficients)->mapWithKeys(fn ($v, $k) => [(string) $k => $v])->toArray())
                            ->numeric()
                            ->required(),
                        Select::make('status')
                            ->label('Статус')
                            ->default(Time::STATUS_PLANNED)
                            ->options(Time::$statuses)
                            ->required(),
                        TextInput::make('title')
                            ->label('Заголовок')
                            ->required()->columnSpanFull(),
                        Textarea::make('description')
                            ->label('Опис')->columnSpanFull(),
                    ])
                    ->defaultItems(0)
                    ->addActionLabel('Додати')
                    // сворачиваемый
                    ->collapsible()
                    // по умолчанию свернутый
                    ->collapsed()
                    // делаем название из поля title
                    ->itemLabel(fn ($state) => ($state['title'] ?? '').
                         ' Час: '.($state['duration'] ?? '').
                         ' Статус: '.(Time::$statuses[$state['status']] ?? '~ Новий ~')
                    )
                    ->columns(4),
            ])
            ->id('timer-section')
            ->columnSpanFull();
    }

    private static function commentsSection()
    {
        return Section::make('Коментарі')
            // ->footer([
            //     ViewField::make('syncActions')
            //         ->view('filament.resources.tasks.sync-buttons')
            //         ->columnSpanFull(),
            // ])
            ->schema([
                Repeater::make('comments')
                    ->relationship('comments')
                    ->label('Коментарі задачі')
                    ->schema([
                        Select::make('user_id')
                            ->label('Автор')
                            ->relationship('user', 'name')
                            ->default(auth()->id())
                            ->required(),

                        Textarea::make('content')
                            ->label('Коментар')
                            ->required()
                            ->rows(3)
                            ->columnSpanFull(),

                        TextInput::make('asana_gid')
                            ->label('Asana GID')
                            ->disabled()
                            ->visible(fn ($state) => ! empty($state))
                            ->hint(fn ($state) => ! empty($state) ? 'Синхронізовано з Asana' : 'Не синхронізовано'),

                        \Filament\Forms\Components\TextInput::make('asana_created_at')
                            ->label('Дата створення в Asana')
                            ->disabled()
                            ->visible(fn ($state) => ! empty($state)),
                    ])
                    ->defaultItems(0)
                    ->addActionLabel('Додати коментар')
                    ->collapsible()
                    ->itemLabel(fn ($state) => (! empty($state['asana_gid']) ? '✅ ' : '⏳ ').
                        substr($state['content'] ?? 'Новий коментар', 0, 50).
                        (strlen($state['content'] ?? '') > 50 ? '...' : '')
                    )
                    ->columns(2)
                    ->orderColumn('id')
                    ->reorderable(false)
                    ->deleteAction(fn (Action $action) => $action->requiresConfirmation())
                    ->cloneAction(fn (Action $action) => $action->label('Клонувати')),
            ])
            ->columnSpanFull();
    }
}
