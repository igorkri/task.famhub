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

                    MarkdownEditor::make('description')
                        ->label('Опис')
                        ->fileAttachmentsDisk('public')
                        ->fileAttachmentsDirectory('task-descriptions')
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
        return Section::make('⏱️ Облік часу')
            ->description('Ведіть облік витраченого часу на завдання')
            ->icon('heroicon-o-clock')
            ->schema([
                // Красивый блок с общей статистикой времени
                Section::make('📊 Загальна статистика')
                    ->schema([
                        ViewField::make('total_time')
                            ->view('components.total-time')
                            ->viewData(fn ($record) => [
                                'times' => optional($record)?->times ?? collect(),
                            ])
                            ->columnSpanFull(),
                    ])
                    ->compact()
                    ->columnSpanFull(),

                // Список записей времени с улучшенным дизайном
                Repeater::make('times')
                    ->relationship('times')
                    ->label('📝 Записи часу')
                    ->schema([
                        // Первая строка: основная информация
                        Flex::make([
                            TimePicker::make('duration')
                                ->label('⏰ Час')
                                ->seconds(true)
                                ->required()
                                ->dehydrateStateUsing(fn ($state) => $state)
                                ->afterStateHydrated(function ($component, $state) {
                                    $component->state($state ?? '00:00:00');
                                })
                                ->grow(false),

                            Select::make('user_id')
                                ->label('👤 Користувач')
                                ->default(auth()->id())
                                ->relationship('user', 'name')
                                ->required()
                                ->grow(false),

                            TextInput::make('coefficient')
                                ->label('📈 Коефіцієнт')
                                ->default(Time::COEFFICIENT_STANDARD)
                                ->numeric()
                                ->step(0.1)
                                ->required()
                                ->grow(false)
                                ->suffix('x'),

                            Select::make('status')
                                ->label('🎯 Статус')
                                ->default(Time::STATUS_PLANNED)
                                ->options(Time::$statuses)
                                ->required()
                                ->grow(false),
                        ])->from('md'),

                        // Вторая строка: заголовок
                        TextInput::make('title')
                            ->label('📋 Заголовок')
                            ->required()
                            ->placeholder('Опишіть що робили...')
                            ->columnSpanFull(),

                        // Третья строка: описание
                        Textarea::make('description')
                            ->label('📄 Детальний опис')
                            ->placeholder('Додаткові деталі роботи...')
                            ->rows(2)
                            ->columnSpanFull(),
                    ])
                    ->defaultItems(0)
                    ->addActionLabel('➕ Додати запис часу')
                    ->collapsible()
                    ->collapsed()
                    ->cloneable()
                    ->reorderable()
                    ->deleteAction(
                        fn (Action $action) => $action
                            ->requiresConfirmation()
                            ->modalHeading('Видалити запис часу?')
                            ->modalDescription('Ця дія незворотна.')
                            ->modalSubmitActionLabel('Видалити')
                    )
                    ->itemLabel(function ($state) {
                        $title = $state['title'] ?? 'Новий запис';
                        $duration = $state['duration'] ?? '00:00:00';
                        $status = Time::$statuses[$state['status'] ?? Time::STATUS_PLANNED] ?? 'Новий';
                        $coefficient = $state['coefficient'] ?? 1;

                        // Додаємо іконки статусу
                        $statusIcon = match ($state['status'] ?? Time::STATUS_PLANNED) {
                            Time::STATUS_PLANNED => '📋',
                            Time::STATUS_IN_PROGRESS => '🔄',
                            Time::STATUS_COMPLETED => '✅',
                            Time::STATUS_PAUSED => '⏸️',
                            default => '📋'
                        };

                        return "{$statusIcon} {$title} • ⏰ {$duration} • 📈 {$coefficient}x • {$status}";
                    })
                    ->extraItemActions([
                        Action::make('duplicate')
                            ->icon('heroicon-o-document-duplicate')
                            ->tooltip('Дублювати')
                            ->action(function (array $arguments, Repeater $component): void {
                                $component->callAction('clone', $arguments);
                            }),
                    ])
                    ->grid(1)
                    ->live(),

                // Подсказки и советы
                Section::make('💡 Підказки')
                    ->schema([
                        \Filament\Forms\Components\Placeholder::make('timer_tips')
                            ->label('')
                            ->content(new \Illuminate\Support\HtmlString(
                                '<div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                                    <div class="bg-blue-50 dark:bg-blue-900/20 p-3 rounded-lg border border-blue-200 dark:border-blue-800">
                                        <div class="font-medium text-blue-800 dark:text-blue-200 mb-1">⏰ Формат часу</div>
                                        <div class="text-blue-600 dark:text-blue-300">Використовуйте формат ГГ:ХХ:СС (години:хвилини:секунди)</div>
                                    </div>
                                    <div class="bg-green-50 dark:bg-green-900/20 p-3 rounded-lg border border-green-200 dark:border-green-800">
                                        <div class="font-medium text-green-800 dark:text-green-200 mb-1">📈 Коефіцієнт</div>
                                        <div class="text-green-600 dark:text-green-300">1.0 - стандарт, 1.5 - складна робота, 0.5 - проста</div>
                                    </div>
                                    <div class="bg-purple-50 dark:bg-purple-900/20 p-3 rounded-lg border border-purple-200 dark:border-purple-800">
                                        <div class="font-medium text-purple-800 dark:text-purple-200 mb-1">🎯 Статуси</div>
                                        <div class="text-purple-600 dark:text-purple-300">Відстежуйте прогрес: Заплановано → В процесі → Завершено</div>
                                    </div>
                                    <div class="bg-amber-50 dark:bg-amber-900/20 p-3 rounded-lg border border-amber-200 dark:border-amber-800">
                                        <div class="font-medium text-amber-800 dark:text-amber-200 mb-1">📝 Заголовки</div>
                                        <div class="text-amber-600 dark:text-amber-300">Вказуйте зрозумілі назви для легкого пошуку</div>
                                    </div>
                                </div>'
                            ))
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed()
                    ->columnSpanFull(),
            ])
            ->id('timer-section')
            ->columnSpanFull();
    }

    private static function commentsSection()
    {
        return Section::make('💬 Коментарі')
            ->description('Обговорення та нотатки по завданню')
            ->icon('heroicon-o-chat-bubble-left-right')
            ->schema([
                Repeater::make('comments')
                    ->relationship('comments')
                    ->label('📝 Коментарі задачі')
                    ->schema([
                        Flex::make([
                            Select::make('user_id')
                                ->label('👤 Автор')
                                ->relationship('user', 'name')
                                ->default(auth()->id())
                                ->required()
                                ->grow(false),

                            TextInput::make('asana_gid')
                                ->label('🔗 Asana GID')
                                ->disabled()
                                ->visible(fn ($state) => ! empty($state))
                                ->hint(fn ($state) => ! empty($state) ? '✅ Синхронізовано з Asana' : '⏳ Не синхронізовано')
                                ->grow(false),

                            \Filament\Forms\Components\TextInput::make('asana_created_at')
                                ->label('📅 Дата створення в Asana')
                                ->disabled()
                                ->visible(fn ($state) => ! empty($state))
                                ->grow(false),
                        ])->from('md'),

                        Textarea::make('content')
                            ->label('💭 Коментар')
                            ->required()
                            ->rows(3)
                            ->placeholder('Напишіть ваш коментар...')
                            ->columnSpanFull(),
                    ])
                    ->defaultItems(0)
                    ->addActionLabel('➕ Додати коментар')
                    ->collapsible()
                    ->collapsed()
                    ->itemLabel(function ($state) {
                        $syncIcon = ! empty($state['asana_gid']) ? '✅' : '⏳';
                        $content = $state['content'] ?? 'Новий коментар';
                        $truncated = substr($content, 0, 50);
                        $truncated .= strlen($content) > 50 ? '...' : '';

                        return "{$syncIcon} {$truncated}";
                    })
                    ->columns(1)
                    ->orderColumn('id')
                    ->reorderable(false)
                    ->deleteAction(fn (Action $action) => $action
                        ->requiresConfirmation()
                        ->modalHeading('Видалити коментар?')
                        ->modalDescription('Ця дія незворотна.')
                        ->modalSubmitActionLabel('Видалити')
                    )
                    ->cloneAction(fn (Action $action) => $action->label('📋 Клонувати')),

                // Подсказки для комментариев
                Section::make('💡 Підказки')
                    ->schema([
                        \Filament\Forms\Components\Placeholder::make('comments_tips')
                            ->label('')
                            ->content(new \Illuminate\Support\HtmlString(
                                '<div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                                    <div class="bg-blue-50 dark:bg-blue-900/20 p-3 rounded-lg border border-blue-200 dark:border-blue-800">
                                        <div class="font-medium text-blue-800 dark:text-blue-200 mb-1">💬 Коментарі</div>
                                        <div class="text-blue-600 dark:text-blue-300">Використовуйте для обговорення деталей завдання</div>
                                    </div>
                                    <div class="bg-green-50 dark:bg-green-900/20 p-3 rounded-lg border border-green-200 dark:border-green-800">
                                        <div class="font-medium text-green-800 dark:text-green-200 mb-1">🔄 Синхронізація</div>
                                        <div class="text-green-600 dark:text-green-300">✅ - синхронізовано з Asana, ⏳ - локальний коментар</div>
                                    </div>
                                </div>'
                            ))
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed()
                    ->columnSpanFull(),
            ])
            ->columnSpanFull();
    }

    private static function customFieldsSection()
    {
        return Section::make('⚙️ Кастомні поля з Asana')
            ->description('Редагуйте поля тут - вони синхронізуються з Asana при збереженні')
            ->icon('heroicon-o-adjustments-horizontal')
            ->headerActions([
                Action::make('auto_calculate_time')
                    ->label('🧮 Автопрорахунок часу')
                    ->icon('heroicon-o-calculator')
                    ->color('success')
                    ->action(function ($livewire, $get) {
                        $record = $livewire->record;
                        if (! $record) {
                            \Filament\Notifications\Notification::make()
                                ->warning()
                                ->title('❌ Немає запису')
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
                                ->title('✅ Час прораховано!')
                                ->body("Оновлено поле 'Час, факт.': {$totalHours} год ({$hours} год {$minutes} хв)")
                                ->send();

                            // Оновлюємо форму (перезавантажуємо сторінку для відображення змін)
                            redirect()->to($livewire->getResource()::getUrl('edit', ['record' => $record]));
                        } else {
                            \Filament\Notifications\Notification::make()
                                ->warning()
                                ->title('⚠️ Поле не знайдено')
                                ->body('Не знайдено кастомне поле "Час, факт." для цього проєкту')
                                ->send();
                        }
                    })
                    ->visible(fn ($livewire) => $livewire->record !== null),
            ])
            ->schema([
                Repeater::make('customFields')
                    ->relationship('customFields')
                    ->label('📋 Поля')
                    ->schema([
                        \Filament\Forms\Components\Hidden::make('asana_gid'),
                        \Filament\Forms\Components\Hidden::make('project_custom_field_id'),
                        \Filament\Forms\Components\Hidden::make('type'),

                        // Название поля с иконкой типа
                        TextInput::make('name')
                            ->label('🏷️ Назва поля')
                            ->disabled()
                            ->dehydrated(true)
                            ->prefixIcon(function ($get) {
                                return match ($get('type')) {
                                    'text' => 'heroicon-o-document-text',
                                    'number' => 'heroicon-o-calculator',
                                    'date' => 'heroicon-o-calendar',
                                    'enum' => 'heroicon-o-list-bullet',
                                    default => 'heroicon-o-question-mark-circle',
                                };
                            })
                            ->columnSpan(1),

                        // Текстове поле
                        Textarea::make('text_value')
                            ->label('📝 Значення')
                            ->rows(2)
                            ->placeholder('Введіть текст...')
                            ->visible(fn ($get) => $get('type') === 'text')
                            ->columnSpan(3),

                        // Числове поле з кнопкою автопрорахунку для часу
                        TextInput::make('number_value')
                            ->label('🔢 Значення')
                            ->numeric()
                            ->step(0.01)
                            ->placeholder('0.00')
                            ->visible(fn ($get) => $get('type') === 'number')
                            ->suffixAction(
                                Action::make('calculate_from_timer')
                                    ->icon('heroicon-o-calculator')
                                    ->tooltip('Порахувати з таймера')
                                    ->color('success')
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
                                                ->title('🧮 Прораховано з таймера')
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
                            ->label('📅 Значення')
                            ->placeholder('Виберіть дату...')
                            ->visible(fn ($get) => $get('type') === 'date')
                            ->columnSpan(3),

                        // Enum (список)
                        Select::make('enum_value_gid')
                            ->label('📋 Значення')
                            ->placeholder('Виберіть варіант...')
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
                        // Получаем иконку для типа поля
                        $typeIcon = match ($state['type'] ?? 'text') {
                            'text' => '📝',
                            'number' => '🔢',
                            'date' => '📅',
                            'enum' => '📋',
                            default => '❓',
                        };

                        // Отримуємо name через projectCustomField, оскільки поле disabled і не зберігається в $state
                        $name = $state['name'] ?? $get('name') ?? 'Поле';

                        $value = match ($state['type'] ?? 'text') {
                            'text' => $state['text_value'] ?? '—',
                            'number' => $state['number_value'] ?? '—',
                            'date' => $state['date_value'] ?? '—',
                            'enum' => $state['enum_value_name'] ?? '—',
                            default => '—',
                        };

                        return "{$typeIcon} {$name}: {$value}";
                    }),

                \Filament\Forms\Components\Placeholder::make('sync_hint')
                    ->label('')
                    ->content(new \Illuminate\Support\HtmlString(
                        '<div class="bg-gradient-to-r from-amber-50 to-orange-50 dark:from-amber-900/20 dark:to-orange-900/20 rounded-lg p-4 border border-amber-200 dark:border-amber-800">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                                <div>
                                    <div class="font-medium text-amber-800 dark:text-amber-200 mb-1">💾 Збереження</div>
                                    <div class="text-amber-600 dark:text-amber-300">Кастомні поля автоматично синхронізуються з Asana при натисканні "Відправити в Asana"</div>
                                </div>
                                <div>
                                    <div class="font-medium text-orange-800 dark:text-orange-200 mb-1">🧮 Підказка</div>
                                    <div class="text-orange-600 dark:text-orange-300">Використовуйте кнопку "🧮 Автопрорахунок часу" вгорі для автоматичного підрахунку часу з таймера</div>
                                </div>
                            </div>
                        </div>'
                    ))
                    ->columnSpanFull(),
            ])
            ->columnSpanFull();
    }
}
