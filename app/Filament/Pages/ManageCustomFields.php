<?php

namespace App\Filament\Pages;

use App\Models\Project;
use App\Models\ProjectCustomField;
use App\Models\TaskCustomField;
use App\Services\AsanaService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Artisan;

class ManageCustomFields extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-adjustments-horizontal';

    protected string $view = 'filament.pages.manage-custom-fields';

    protected static ?string $navigationLabel = 'Кастомні поля';

    protected static ?string $title = 'Управління кастомними полями Asana';

    protected static string|\UnitEnum|null $navigationGroup = 'Налаштування';

    protected static ?int $navigationSort = 100;

    public array $stats = [];

    public array $projectsData = [];

    public function mount(): void
    {
        $this->loadData();
    }

    protected function loadData(): void
    {
        // Статистика
        $this->stats = [
            'projects' => Project::whereNotNull('asana_id')->count(),
            'project_fields' => ProjectCustomField::count(),
            'task_fields' => TaskCustomField::count(),
        ];

        // Дані проєктів
        $projects = Project::whereNotNull('asana_id')
            ->with('customFields')
            ->get();

        $this->projectsData = $projects->map(function ($project) {
            $customFields = $project->customFields;
            $taskFieldsCount = TaskCustomField::whereHas('task', function ($q) use ($project) {
                $q->where('project_id', $project->id);
            })->count();

            return [
                'id' => $project->id,
                'name' => $project->name,
                'fields_count' => $customFields->count(),
                'fields' => $customFields->map(fn ($f) => [
                    'name' => $f->name,
                    'type' => $f->type,
                    'enum_options' => $f->enum_options,
                ])->toArray(),
                'task_values_count' => $taskFieldsCount,
                'has_fields' => $customFields->count() > 0,
            ];
        })->toArray();
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

                        $this->loadData();

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

                        $this->loadData();

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

                    $this->loadData();

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
                    $this->loadData();

                    Notification::make()
                        ->success()
                        ->title('Дані оновлено')
                        ->send();
                }),
        ];
    }
}

