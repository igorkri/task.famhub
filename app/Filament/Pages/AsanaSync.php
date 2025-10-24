<?php

namespace App\Filament\Pages;

use App\Models\Project;
use App\Models\Task;
use App\Models\TaskComment;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Artisan;

class AsanaSync extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrow-path';

    protected string $view = 'filament.pages.asana-sync';

    protected static ?string $navigationLabel = 'Синхронізація Asana';

    protected static ?string $title = 'Синхронізація даних з Asana';

    protected static string|\UnitEnum|null $navigationGroup = 'Налаштування';

    protected static ?int $navigationSort = 90;

    public array $stats = [];

    public function mount(): void
    {
        $this->loadStats();
    }

    protected function loadStats(): void
    {
        $this->stats = [
            'projects' => Project::whereNotNull('asana_id')->count(),
            'tasks' => Task::whereNotNull('asana_id')->count(),
            'comments' => TaskComment::whereNotNull('asana_id')->count(),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('syncAll')
                ->label('Синхронізувати все')
                ->icon('heroicon-o-arrow-path')
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading('Синхронізувати всі дані з Asana?')
                ->modalDescription('Це виконає повну синхронізацію проєктів, тасків, коментарів та кастомних полів. Може зайняти кілька хвилин.')
                ->modalSubmitActionLabel('Синхронізувати')
                ->action(function () {
                    try {
                        Artisan::call('asana:sync', ['--no-interaction' => true]);

                        $this->loadStats();

                        Notification::make()
                            ->success()
                            ->title('Синхронізацію запущено!')
                            ->body('Повна синхронізація виконується у фоні. Оновіть сторінку через хвилину.')
                            ->send();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->danger()
                            ->title('Помилка синхронізації')
                            ->body($e->getMessage())
                            ->send();
                    }
                }),

            Action::make('refresh')
                ->label('Оновити')
                ->icon('heroicon-o-arrow-path')
                ->action(function () {
                    $this->loadStats();

                    Notification::make()
                        ->success()
                        ->title('Статистику оновлено')
                        ->send();
                }),
        ];
    }

    public function syncTasksAction(): Action
    {
        return Action::make('syncTasks')
            ->label('Синхронізувати')
            ->icon('heroicon-o-arrow-path')
            ->color('primary')
            ->requiresConfirmation()
            ->modalHeading('Синхронізувати таски?')
            ->modalDescription('Завантажить всі таски з Asana без коментарів.')
            ->action(function () {
                try {
                    Artisan::call('asana:sync-tasks', ['--no-interaction' => true]);

                    $this->loadStats();

                    Notification::make()
                        ->success()
                        ->title('Синхронізацію запущено!')
                        ->body('Таски синхронізуються у фоні.')
                        ->send();
                } catch (\Exception $e) {
                    Notification::make()
                        ->danger()
                        ->title('Помилка')
                        ->body($e->getMessage())
                        ->send();
                }
            });
    }

    public function syncTasksFullAction(): Action
    {
        return Action::make('syncTasksFull')
            ->label('Синхронізувати повністю')
            ->icon('heroicon-o-arrow-path')
            ->color('success')
            ->requiresConfirmation()
            ->modalHeading('Повна синхронізація тасків?')
            ->modalDescription('Завантажить таски з коментарями та вкладеннями. Може зайняти багато часу.')
            ->action(function () {
                try {
                    Artisan::call('asana:sync-tasks', [
                        '--full' => true,
                        '--no-interaction' => true,
                    ]);

                    $this->loadStats();

                    Notification::make()
                        ->success()
                        ->title('Повна синхронізація запущена!')
                        ->body('Це може зайняти кілька хвилин.')
                        ->send();
                } catch (\Exception $e) {
                    Notification::make()
                        ->danger()
                        ->title('Помилка')
                        ->body($e->getMessage())
                        ->send();
                }
            });
    }

    public function syncProjectsAction(): Action
    {
        return Action::make('syncProjects')
            ->label('Синхронізувати')
            ->icon('heroicon-o-arrow-path')
            ->color('primary')
            ->requiresConfirmation()
            ->modalHeading('Синхронізувати проєкти?')
            ->modalDescription('Оновить список проєктів з Asana.')
            ->action(function () {
                try {
                    Artisan::call('asana:sync', [
                        '--projects-only' => true,
                        '--no-interaction' => true,
                    ]);

                    $this->loadStats();

                    Notification::make()
                        ->success()
                        ->title('Проєкти синхронізовано!')
                        ->send();
                } catch (\Exception $e) {
                    Notification::make()
                        ->danger()
                        ->title('Помилка')
                        ->body($e->getMessage())
                        ->send();
                }
            });
    }

    public function syncCustomFieldsSettingsAction(): Action
    {
        return Action::make('syncCustomFieldsSettings')
            ->label('Синхронізувати')
            ->icon('heroicon-o-arrow-path')
            ->color('success')
            ->requiresConfirmation()
            ->modalHeading('Синхронізувати налаштування кастомних полів?')
            ->modalDescription('Завантажить налаштування полів з проєктів Asana.')
            ->action(function () {
                try {
                    Artisan::call('asana:sync-project-custom-fields', ['--no-interaction' => true]);

                    $this->loadStats();

                    Notification::make()
                        ->success()
                        ->title('Налаштування синхронізовано!')
                        ->send();
                } catch (\Exception $e) {
                    Notification::make()
                        ->danger()
                        ->title('Помилка')
                        ->body($e->getMessage())
                        ->send();
                }
            });
    }

    public function syncCustomFieldsValuesAction(): Action
    {
        return Action::make('syncCustomFieldsValues')
            ->label('Синхронізувати')
            ->icon('heroicon-o-arrow-path')
            ->color('success')
            ->requiresConfirmation()
            ->modalHeading('Синхронізувати значення кастомних полів?')
            ->modalDescription('Завантажить значення полів з усіх тасків. Може зайняти час.')
            ->action(function () {
                try {
                    Artisan::call('asana:sync-custom-fields', ['--no-interaction' => true]);

                    $this->loadStats();

                    Notification::make()
                        ->success()
                        ->title('Синхронізація значень запущена!')
                        ->body('Виконується у фоні.')
                        ->send();
                } catch (\Exception $e) {
                    Notification::make()
                        ->danger()
                        ->title('Помилка')
                        ->body($e->getMessage())
                        ->send();
                }
            });
    }

    public function createWebhooksAction(): Action
    {
        return Action::make('createWebhooks')
            ->label('Створити webhooks')
            ->icon('heroicon-o-plus-circle')
            ->color('warning')
            ->requiresConfirmation()
            ->modalHeading('Створити webhooks для всіх проєктів?')
            ->modalDescription('Це створить webhooks для автоматичної синхронізації змін з Asana.')
            ->action(function () {
                try {
                    Artisan::call('asana:create-webhooks', ['--no-interaction' => true]);

                    Notification::make()
                        ->success()
                        ->title('Webhooks створено!')
                        ->body('Автоматична синхронізація активована.')
                        ->send();
                } catch (\Exception $e) {
                    Notification::make()
                        ->danger()
                        ->title('Помилка')
                        ->body($e->getMessage())
                        ->send();
                }
            });
    }

    public function checkWebhooksAction(): Action
    {
        return Action::make('checkWebhooks')
            ->label('Перевірити статус')
            ->icon('heroicon-o-information-circle')
            ->color('info')
            ->action(function () {
                try {
                    Artisan::call('asana:manage-webhooks', [
                        'action' => 'list',
                        '--no-interaction' => true,
                    ]);

                    Notification::make()
                        ->info()
                        ->title('Статус webhooks')
                        ->body('Перевірте консоль або логи для деталей.')
                        ->send();
                } catch (\Exception $e) {
                    Notification::make()
                        ->danger()
                        ->title('Помилка')
                        ->body($e->getMessage())
                        ->send();
                }
            });
    }
}
