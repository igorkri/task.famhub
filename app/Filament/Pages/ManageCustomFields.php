<?php

namespace App\Filament\Pages;

use App\Models\Project;
use App\Models\ProjectCustomField;
use App\Models\TaskCustomField;
use App\Services\AsanaService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Artisan;

class ManageCustomFields extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-adjustments-horizontal';

    protected string $view = 'filament.pages.manage-custom-fields';

    protected static ?string $navigationLabel = 'Кастомні поля';

    protected static ?string $title = 'Управління кастомними полями Asana';

    protected static string|\UnitEnum|null $navigationGroup = 'Налаштування';

    protected static ?int $navigationSort = 100;

    public array $stats = [];

    public function mount(): void
    {
        $this->loadStats();
    }

    protected function loadStats(): void
    {
        // Статистика
        $this->stats = [
            'projects' => Project::whereNotNull('asana_id')->count(),
            'project_fields' => ProjectCustomField::count(),
            'task_fields' => TaskCustomField::count(),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                ProjectCustomField::query()
                    ->with(['project'])
                    ->join('projects', 'project_custom_fields.project_id', '=', 'projects.id')
                    ->select('project_custom_fields.*')
            )
            ->defaultSort('projects.name')
            ->columns([
                TextColumn::make('project.name')
                    ->label('Проєкт')
                    ->sortable()
                    ->searchable()
                    ->icon('heroicon-o-folder')
                    ->weight('bold')
                    ->color('primary'),

                TextColumn::make('name')
                    ->label('Назва поля')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),

                TextColumn::make('type')
                    ->label('Тип')
                    ->sortable()
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'enum', 'multi_enum' => 'info',
                        'number' => 'warning',
                        'text' => 'gray',
                        'date' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'enum' => 'Список',
                        'multi_enum' => 'Мультисписок',
                        'number' => 'Число',
                        'text' => 'Текст',
                        'date' => 'Дата',
                        default => $state,
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'enum', 'multi_enum' => 'heroicon-o-list-bullet',
                        'number' => 'heroicon-o-hashtag',
                        'text' => 'heroicon-o-document-text',
                        'date' => 'heroicon-o-calendar',
                        default => 'heroicon-o-cog',
                    }),

                TextColumn::make('enum_options')
                    ->label('Варіанти')
                    ->html()
                    ->formatStateUsing(function ($state, ProjectCustomField $record): string {
                        if (! in_array($record->type, ['enum', 'multi_enum'])) {
                            return '<span class="text-gray-400 text-xs">—</span>';
                        }

                        if (empty($state) || ! is_array($state)) {
                            return '<span class="text-gray-400 text-xs italic">Немає варіантів</span>';
                        }

                        // Беремо тільки перші 5 варіантів
                        $totalCount = count($state);
                        $displayOptions = array_slice($state, 0, 5);
                        $remaining = max(0, $totalCount - 5);

                        $badges = [];
                        foreach ($displayOptions as $option) {
                            // Витягуємо ТІЛЬКИ поле name
                            if (is_array($option) && isset($option['name'])) {
                                $name = $option['name'];
                            } else {
                                continue;
                            }

                            $badges[] = '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-primary-50 dark:bg-primary-900/30 text-primary-700 dark:text-primary-300 border border-primary-200 dark:border-primary-800">'.e($name).'</span>';
                        }

                        if ($remaining > 0) {
                            $badges[] = '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 font-medium">+'.$remaining.'</span>';
                        }

                        return '<div class="flex flex-wrap gap-1">'.implode('', $badges).'</div>';
                    })
                    ->toggleable(),

                TextColumn::make('taskCustomFields_count')
                    ->counts('taskCustomFields')
                    ->label('Використань')
                    ->sortable()
                    ->alignCenter()
                    ->color('success')
                    ->formatStateUsing(fn ($state) => $state ?: '0'),

                TextColumn::make('is_required')
                    ->label("Обов'язкове")
                    ->sortable()
                    ->alignCenter()
                    ->formatStateUsing(fn ($state) => $state ? '✓' : '—')
                    ->color(fn ($state) => $state ? 'success' : 'gray')
                    ->toggleable(),

                TextColumn::make('description')
                    ->label('Опис')
                    ->limit(50)
                    ->tooltip(fn ($state) => $state)
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('asana_gid')
                    ->label('Asana GID')
                    ->copyable()
                    ->copyMessage('GID скопійовано')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Створено')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                //
            ])
            ->bulkActions([
                //
            ])
            ->emptyStateHeading('Кастомні поля не знайдено')
            ->emptyStateDescription('Натисніть "Синхронізувати поля проєктів" для завантаження полів з Asana')
            ->emptyStateIcon('heroicon-o-adjustments-horizontal')
            ->paginated([10, 25, 50, 100]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('syncProjects')
                ->label('Синхронізувати поля проєктів')
                ->icon('heroicon-o-arrow-path')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Синхронізувати кастомні поля з Asana?')
                ->modalDescription('Це завантажить налаштування кастомних полів з усіх проєктів Asana.')
                ->modalSubmitActionLabel('Синхронізувати')
                ->action(function () {
                    try {
                        $service = app(AsanaService::class);
                        $projects = Project::whereNotNull('asana_id')->get();
                        $totalFields = 0;
                        $errors = [];

                        foreach ($projects as $project) {
                            try {
                                $fields = $service->getProjectCustomFields($project->asana_id);

                                foreach ($fields as $field) {
                                    ProjectCustomField::updateOrCreate(
                                        [
                                            'project_id' => $project->id,
                                            'asana_gid' => $field['gid'],
                                        ],
                                        [
                                            'name' => $field['name'],
                                            'type' => $field['type'],
                                            'description' => $field['description'],
                                            'enum_options' => $field['enum_options'],
                                            'is_required' => $field['is_required'],
                                            'precision' => $field['precision'],
                                        ]
                                    );
                                    $totalFields++;
                                }
                            } catch (\Exception $e) {
                                $errors[] = $project->name.': '.$e->getMessage();
                            }
                        }

                        $this->loadStats();

                        if (empty($errors)) {
                            Notification::make()
                                ->success()
                                ->title('Успішно синхронізовано!')
                                ->body("Синхронізовано {$projects->count()} проєктів, {$totalFields} полів")
                                ->send();
                        } else {
                            Notification::make()
                                ->warning()
                                ->title('Синхронізовано з помилками')
                                ->body('Помилок: '.count($errors).'. Перевірте логи.')
                                ->send();
                        }
                    } catch (\Exception $e) {
                        Notification::make()
                            ->danger()
                            ->title('Помилка синхронізації')
                            ->body($e->getMessage())
                            ->send();
                    }
                }),

            Action::make('syncTasks')
                ->label('Синхронізувати значення тасків')
                ->icon('heroicon-o-arrow-path')
                ->color('info')
                ->requiresConfirmation()
                ->modalHeading('Синхронізувати значення полів тасків?')
                ->modalDescription('Це завантажить значення кастомних полів з усіх тасків. Може зайняти час.')
                ->modalSubmitActionLabel('Синхронізувати')
                ->action(function () {
                    if (ProjectCustomField::count() === 0) {
                        Notification::make()
                            ->warning()
                            ->title('Спочатку синхронізуйте поля проєктів')
                            ->body('Натисніть "Синхронізувати поля проєктів" спочатку')
                            ->send();

                        return;
                    }

                    try {
                        // Запускаємо команду асинхронно
                        Artisan::call('asana:sync-custom-fields');

                        $this->loadStats();

                        Notification::make()
                            ->success()
                            ->title('Синхронізацію запущено!')
                            ->body('Синхронізація значень тасків виконується у фоні. Оновіть сторінку через хвилину.')
                            ->send();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->danger()
                            ->title('Помилка синхронізації')
                            ->body($e->getMessage())
                            ->send();
                    }
                }),

            Action::make('clear')
                ->label('Очистити все')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Видалити всі кастомні поля?')
                ->modalDescription('Це видалить ВСІ налаштування і значення кастомних полів з бази даних. Дію неможливо скасувати!')
                ->modalSubmitActionLabel('Так, видалити все')
                ->action(function () {
                    $taskFieldsCount = TaskCustomField::count();
                    $projectFieldsCount = ProjectCustomField::count();

                    TaskCustomField::truncate();
                    ProjectCustomField::truncate();

                    $this->loadStats();

                    Notification::make()
                        ->success()
                        ->title('Кастомні поля видалено')
                        ->body("Видалено {$projectFieldsCount} налаштувань і {$taskFieldsCount} значень")
                        ->send();
                }),

            Action::make('refresh')
                ->label('Оновити')
                ->icon('heroicon-o-arrow-path')
                ->action(function () {
                    $this->loadStats();

                    Notification::make()
                        ->success()
                        ->title('Дані оновлено')
                        ->send();
                }),
        ];
    }
}
