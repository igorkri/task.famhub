<?php

namespace App\Filament\Resources\Tasks\Schemas;

use App\Models\Task;
use App\Models\Time;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
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
                        Tabs\Tab::make('Кастомні поля')
                            ->icon('heroicon-o-adjustments-horizontal')
                            ->badge(fn ($record) => optional($record)?->customFields()->count() ?? 0)
                            ->schema([
                                self::customFieldsSection(),
                            ])
                            ->visible(fn ($record) => $record !== null && $record->customFields()->count() > 0),
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

                    RichEditor::make('description')
                        ->label('Опис')
                        ->fileAttachmentsDisk('public')
                        ->fileAttachmentsDirectory('task-descriptions')
                        ->fileAttachmentsVisibility('public')
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

                    Section::make('Робочі параметри')
                        ->schema([
                            Select::make('status')
                                ->label('Статус')
                                ->options(Task::$statuses)
                                ->required()
                                ->default(Task::STATUS_NEW),

                            //                            Select::make('priority')
                            //                                ->label('Пріоритет')
                            //                                ->options(Task::$priorities)
                            //                                ->nullable(),

                            Select::make('project_id')
                                ->label('Проект')
                                ->relationship('project', 'name')
                                ->visible(fn ($record) => optional($record)?->project_id == null)
                                ->required(),

                            Select::make('user_id')
                                ->label('Виконавець')
                                ->visible(fn ($record) => optional($record)?->user_id == null)
                                ->relationship('user', 'name'),

                            //                            DatePicker::make('deadline')
                            //                                ->label('Дедлайн'),
                            //
                            //                            DateTimePicker::make('start_date')
                            //                                ->label('Початок'),
                            //
                            //                            DateTimePicker::make('end_date')
                            //                                ->label('Завершення'),
                            //
                            //                            TextInput::make('progress')
                            //                                ->label('Прогрес (%)')
                            //                                ->numeric()
                            //                                ->default(0),
                        ])
                        ->collapsible()
                        ->collapsed(false),
                ])
                ->grow(false)
                ->maxWidth('300px'),
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

    private static function customFieldsSection()
    {
        return Section::make('Кастомні поля з Asana')
            ->description('Редагуйте поля тут - вони синхронізуються з Asana при збереженні')
            ->headerActions([
                Action::make('auto_calculate_time')
                    ->label('🔄 Автопрорахунок часу')
                    ->icon('heroicon-o-calculator')
                    ->color('success')
                    ->action(function ($livewire, $get) {
                        $record = $livewire->record;
                        if (! $record) {
                            \Filament\Notifications\Notification::make()
                                ->warning()
                                ->title('Немає запису')
                                ->body('Спочатку збережіть таск')
                                ->send();

                            return;
                        }

                        // Підраховуємо загальний час з таймера
                        $totalSeconds = \App\Models\Time::where('task_id', $record->id)->sum('duration');
                        $totalHours = round($totalSeconds / 3600, 2);

                        // Знаходимо кастомне поле "Час, факт." та оновлюємо
                        $customFields = $record->customFields;
                        $updated = false;

                        foreach ($customFields as $field) {
                            // Шукаємо поле з назвою що містить "факт" або "spent"
                            if (stripos($field->name, 'факт') !== false || stripos($field->name, 'spent') !== false) {
                                $field->update(['number_value' => $totalHours]);
                                $updated = true;
                                break;
                            }
                        }

                        if ($updated) {
                            $hours = floor($totalHours);
                            $minutes = round(($totalHours - $hours) * 60);

                            \Filament\Notifications\Notification::make()
                                ->success()
                                ->title('Час прораховано!')
                                ->body("Оновлено поле 'Час, факт.': {$totalHours} год ({$hours} год {$minutes} хв)")
                                ->send();

                            // Оновлюємо форму (перезавантажуємо сторінку для відображення змін)
                            redirect()->to($livewire->getResource()::getUrl('edit', ['record' => $record]));
                        } else {
                            \Filament\Notifications\Notification::make()
                                ->warning()
                                ->title('Поле не знайдено')
                                ->body('Не знайдено кастомне поле "Час, факт." для цього проєкту')
                                ->send();
                        }
                    })
                    ->visible(fn ($livewire) => $livewire->record !== null),
            ])
            ->schema([
                Repeater::make('customFields')
                    ->relationship('customFields')
                    ->label('Поля')
                    ->schema([
                        \Filament\Forms\Components\Hidden::make('asana_gid'),
                        \Filament\Forms\Components\Hidden::make('project_custom_field_id'),
                        \Filament\Forms\Components\Hidden::make('type'),

                        TextInput::make('name')
                            ->label('Назва поля')
                            ->disabled()
                            ->dehydrated(true)
                            ->columnSpan(1),

                        // Текстове поле
                        Textarea::make('text_value')
                            ->label('Значення')
                            ->rows(2)
                            ->visible(fn ($get) => $get('type') === 'text')
                            ->columnSpan(3),

                        // Числове поле з кнопкою автопрорахунку для часу
                        TextInput::make('number_value')
                            ->label('Значення')
                            ->numeric()
                            ->step(0.01)
                            ->visible(fn ($get) => $get('type') === 'number')
                            ->suffixAction(
                                Action::make('calculate_from_timer')
                                    ->icon('heroicon-o-calculator')
                                    ->tooltip('Порахувати з таймера')
                                    ->action(function ($set, $get, $livewire, $record) {
                                        if (! $livewire->record) {
                                            return;
                                        }

                                        // Якщо це поле "Час, факт."
                                        $fieldName = $get('name');
                                        if (stripos($fieldName, 'факт') !== false || stripos($fieldName, 'spent') !== false) {
                                            $totalSeconds = \App\Models\Time::where('task_id', $livewire->record->id)->sum('duration');
                                            $totalMinutes = round($totalSeconds / 60);

                                            $set('number_value', $totalMinutes);

                                            $hours = floor($totalMinutes / 60);
                                            $minutes = $totalMinutes % 60;

                                            \Filament\Notifications\Notification::make()
                                                ->success()
                                                ->title('Прораховано з таймера')
                                                ->body("{$totalMinutes} хв ({$hours} год {$minutes} хв)")
                                                ->send();
                                        }
                                    })
                                    ->visible(function ($get, $livewire) {
                                        $fieldName = $get('name') ?? '';

                                        return $livewire->record !== null &&
                                               (stripos($fieldName, 'факт') !== false || stripos($fieldName, 'spent') !== false);
                                    })
                            )
                            ->columnSpan(3),

                        // Дата
                        DatePicker::make('date_value')
                            ->label('Значення')
                            ->visible(fn ($get) => $get('type') === 'date')
                            ->columnSpan(3),

                        // Enum (список)
                        Select::make('enum_value_gid')
                            ->label('Значення')
                            ->options(function ($get, $record) {
                                if (! $record || ! $record->projectCustomField) {
                                    return [];
                                }

                                $options = $record->projectCustomField->enum_options ?? [];

                                return collect($options)->pluck('name', 'gid')->toArray();
                            })
                            ->visible(fn ($get) => $get('type') === 'enum')
                            ->afterStateUpdated(function ($state, $set, $record) {
                                if ($state && $record && $record->projectCustomField) {
                                    $option = collect($record->projectCustomField->enum_options ?? [])
                                        ->firstWhere('gid', $state);
                                    if ($option) {
                                        $set('enum_value_name', $option['name']);
                                    }
                                }
                            })
                            ->live()
                            ->columnSpan(3),

                        \Filament\Forms\Components\Hidden::make('enum_value_name'),
                    ])
                    ->columns(4)
                    ->addable(false)
                    ->deletable(false)
                    ->reorderable(false)
                    ->collapsible()
                    ->itemLabel(function ($state, $get) {
                        // Отримуємо name через projectCustomField, оскільки поле disabled і не зберігається в $state
                        $name = $state['name'] ?? $get('name') ?? 'Поле';

                        $value = match ($state['type'] ?? 'text') {
                            'text' => $state['text_value'] ?? '—',
                            'number' => $state['number_value'] ?? '—',
                            'date' => $state['date_value'] ?? '—',
                            'enum' => $state['enum_value_name'] ?? '—',
                            default => '—',
                        };

                        return "{$name}: {$value}";
                    }),

                \Filament\Forms\Components\Placeholder::make('sync_hint')
                    ->label('')
                    ->content(new \Illuminate\Support\HtmlString(
                        '<div class="text-sm text-gray-600 dark:text-gray-400 mt-2">
                            💾 <strong>Збереження:</strong> Кастомні поля автоматично синхронізуються з Asana при натисканні "Відправити в Asana"<br>
                            🔄 <strong>Підказка:</strong> Використовуйте кнопку "🔄 Автопрорахунок часу" вгорі для автоматичного підрахунку часу з таймера
                        </div>'
                    ))
                    ->columnSpanFull(),
            ])
            ->columnSpanFull();
    }
}
