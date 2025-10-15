<?php

namespace App\Filament\Resources\Tasks\Pages;

use App\Filament\Resources\Tasks\TaskResource;
use App\Models\Section;
use App\Models\TaskComment;
use App\Models\User;
use App\Services\AsanaService;
use Asana\Errors\AsanaError;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\Section as FormSection;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Log;

class EditTask extends EditRecord
{
    protected static string $resource = TaskResource::class;

    protected static bool $hasStickyFooter = false;

    protected array $pendingComments = [];

    protected $listeners = [
        'timer-stopped' => 'refreshTimerData',
        'refreshComponent' => 'refreshTimerData',
    ];

    protected function getHeaderActions(): array
    {
        return [
            $this->getCancelFormAction()
                ->label('До списку')
                ->formId('form')
                ->icon('heroicon-m-arrow-left')
                ->labeledFrom('md')
                ->extraAttributes([
                    'x-data' => '{}', // Убираем зависимость от filamentFormButton
                ]),

            DeleteAction::make()
                ->icon('heroicon-m-trash')
                ->labeledFrom('md'),
        ];
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            FormSection::make('sync')
                ->schema([])
                ->afterHeader([
                    Action::make('syncFromAsana')
                        ->label('Отримати з Asana')
                        ->icon('heroicon-m-arrow-path')
                        ->color('info')
                        ->action(function (): void {
                            // Принудительно сохраняем форму перед синхронизацией
                            $this->save();

                            $this->syncFromAsana();
                        }),

                    Action::make('syncToAsana')
                        ->label('Відправити в Asana')
                        ->icon('heroicon-m-paper-airplane')
                        ->color('primary')
                        ->action(function (): void {
                            $this->save();

                            $this->syncToAsana();
                        }),
                ]),

            $this->getFormContentComponent(),
            $this->getRelationManagersContentComponent(),
        ]);
    }

    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction()
                ->keyBindings(['mod+s']),
            $this->getCancelFormAction(),
        ];
    }

    public function syncCommentsFromAsana(): void
    {
        if (! $this->record->gid) {
            Notification::make()
                ->danger()
                ->title('Помилка')
                ->body('Задача не має GID з Asana')
                ->send();

            return;
        }

        $service = app(AsanaService::class);
        try {
            $asanaComments = $service->getTaskComments($this->record->gid);

            foreach ($asanaComments as $asanaComment) {
                // Проверяем, существует ли уже комментарий с таким gid
                $existingComment = TaskComment::where('asana_gid', $asanaComment['gid'])->first();

                if (! $existingComment) {
                    // Находим пользователя по email из Asana
                    $user = null;
                    if (isset($asanaComment['created_by']['email'])) {
                        $user = User::where('email', $asanaComment['created_by']['email'])->first();
                    }

                    // Создаем новый комментарий
                    TaskComment::create([
                        'task_id' => $this->record->id,
                        'user_id' => $user ? $user->id : auth()->id(),
                        'asana_gid' => $asanaComment['gid'],
                        'content' => $asanaComment['text'],
                        'asana_created_at' => isset($asanaComment['created_at']) ? Carbon::parse($asanaComment['created_at']) : now(),
                    ]);
                }
            }

            Notification::make()
                ->success()
                ->title('Коментарі успішно отримані з Asana')
                ->body('Синхронізовано '.count($asanaComments).' коментарів')
                ->send();

            $this->refresh();
        } catch (\Exception $e) {
            Notification::make()
                ->danger()
                ->title('Помилка при отриманні коментарів')
                ->body($e->getMessage())
                ->send();
        }
    }

    public function syncCommentsToAsana(): void
    {
        if (! $this->record->gid) {
            Notification::make()
                ->danger()
                ->title('Помилка')
                ->body('Задача не має GID з Asana')
                ->send();

            return;
        }

        $service = app(AsanaService::class);
        $syncedCount = 0;
        $errorCount = 0;

        // Получаем все комментарии без asana_gid (не синхронизированные)
        $unsyncedComments = $this->record->comments()->whereNull('asana_gid')->get();

        foreach ($unsyncedComments as $comment) {
            try {
                $result = $service->addCommentToTask($this->record->gid, $comment->content);

                // Обновляем комментарий с GID из Asana
                $comment->update([
                    'asana_gid' => $result['gid'] ?? null,
                ]);

                $syncedCount++;

                Log::info('Comment synced to Asana', [
                    'task_id' => $this->record->id,
                    'comment_id' => $comment->id,
                    'comment_gid' => $result['gid'] ?? null,
                ]);
            } catch (\Exception $e) {
                $errorCount++;
                Log::error('Failed to sync comment to Asana', [
                    'task_id' => $this->record->id,
                    'comment_id' => $comment->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if ($syncedCount > 0) {
            Notification::make()
                ->success()
                ->title('Коментарі відправлені в Asana')
                ->body("Успішно відправлено {$syncedCount} коментарів".
                      ($errorCount > 0 ? ", помилок: {$errorCount}" : ''))
                ->send();
        } elseif ($errorCount > 0) {
            Notification::make()
                ->danger()
                ->title('Помилка відправки коментарів')
                ->body("Помилок при відправці: {$errorCount}")
                ->send();
        } else {
            Notification::make()
                ->info()
                ->title('Немає нових коментарів')
                ->body('Всі коментарі вже синхронізовані з Asana')
                ->send();
        }

        $this->refresh();
    }

    public function syncFromAsana(): void
    {
        $service = app(AsanaService::class);
        try {
            $data = $service->getTaskDetails($this->record->gid);

            $updateData = [
                'title' => $data['name'] ?? $this->record->title,
                'description' => $data['notes'] ?? $this->record->description,
                'is_completed' => $data['completed'] ?? $this->record->is_completed,
            ];

            // Даты создания и обновления из Asana
            if (isset($data['created_at']) && $data['created_at']) {
                try {
                    $updateData['created_at'] = Carbon::parse($data['created_at']);
                } catch (\Exception $e) {
                    Log::warning('Failed to parse Asana created_at', [
                        'task_id' => $this->record->id,
                        'created_at' => $data['created_at'],
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            if (isset($data['modified_at']) && $data['modified_at']) {
                try {
                    $updateData['updated_at'] = Carbon::parse($data['modified_at']);
                } catch (\Exception $e) {
                    Log::warning('Failed to parse Asana modified_at', [
                        'task_id' => $this->record->id,
                        'modified_at' => $data['modified_at'],
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Дедлайн
            if (isset($data['due_on']) && $data['due_on']) {
                $updateData['deadline'] = $data['due_on'];
            }

            // Дата начала
            if (isset($data['start_on']) && $data['start_on']) {
                $updateData['start_date'] = $data['start_on'];
            }

            // Исполнитель
            if (isset($data['assignee']) && $data['assignee']) {
                $assigneeGid = $data['assignee']['gid'] ?? null;
                if ($assigneeGid) {
                    $user = \App\Models\User::where('asana_gid', $assigneeGid)->first();
                    if ($user) {
                        $updateData['user_id'] = $user->id;
                    }
                }
            }

            // Статус на основе секции
            if (isset($data['memberships']) && is_array($data['memberships'])) {
                foreach ($data['memberships'] as $membership) {
                    if (isset($membership['section']) && $membership['section']) {
                        $section = \App\Models\Section::where('asana_gid', $membership['section']['gid'])->first();
                        if ($section && $section->status) {
                            $updateData['status'] = $section->status;
                            break;
                        }
                    }
                }
            }

            // Кастомные поля
            if (isset($data['custom_fields']) && is_array($data['custom_fields'])) {
                foreach ($data['custom_fields'] as $customField) {
                    $fieldGid = $customField['gid'] ?? null;
                    $value = $customField['enum_value'] ?? $customField['number_value'] ?? $customField['text_value'] ?? null;

                    if ($fieldGid === '1202674799521449' && $value) { // Приоритет
                        $priorityMap = [
                            'Високий' => 'high',
                            'Средній' => 'medium',
                            'Низький' => 'low',
                            'Призупинена' => 'low', // или добавить новый статус
                        ];
                        $valueName = is_array($value) ? ($value['name'] ?? '') : '';
                        $updateData['priority'] = $priorityMap[$valueName] ?? 'low';
                    }

                    if ($fieldGid === '1205860710071790' && $value) { // Тип задачі
                        // Можно маппить на status или добавить новое поле
                        // Пока пропустим или добавим в status
                        $typeMap = [
                            'Помилка сайт' => 'needs_clarification',
                            'Помилка в 1С' => 'needs_clarification',
                            'Нова функція' => 'new',
                            'Покращення' => 'in_progress',
                            'Обслуговування' => 'in_progress',
                            'Новий проект (розробка)' => 'new',
                        ];
                        $valueName = is_array($value) ? ($value['name'] ?? '') : '';
                        if (! isset($updateData['status'])) {
                            $updateData['status'] = $typeMap[$valueName] ?? 'new';
                        }
                    }

                    if ($fieldGid === '1202687202895300' && isset($customField['number_value'])) { // Бюджет (часы план)
                        $updateData['budget'] = (float) $customField['number_value'];
                    }

                    if ($fieldGid === '1202687202895302' && isset($customField['number_value'])) { // Витрачено (часы факт)
                        $updateData['spent'] = (float) $customField['number_value'];
                    }
                }
            }

            // dd([
            //                 'membership' => $membership,
            //                 'section' => $section,
            //                 'status' => $section ? $section->status : null,
            //                 'updateData' => $updateData,
            //             ]);
            $this->record->update($updateData);

            Notification::make()
                ->success()
                ->title('Синхронізація з Asana успішна')
                ->send();
            $this->refresh();
            $this->fillForm($this->record->fresh()->toArray());

            // Синхронизируем комментарии после обновления задачи
            $this->syncCommentsFromAsana();
        } catch (AsanaError $e) {
            Notification::make()
                ->danger()
                ->title('Помилка синхронізації з Asana')
                ->body($e->getMessage())
                ->send();
        }
    }

    public function syncToAsana(): void
    {
        if (! $this->record->gid) {
            Notification::make()
                ->danger()
                ->title('Помилка')
                ->body('Задача не має GID з Asana')
                ->send();

            // створюємо задачу в Asana і отримуємо gid
            $this->createTaskInAsana();
        }

        $service = app(AsanaService::class);
        $payload = [
            'name' => $this->record->title,
            'notes' => $this->record->description ?? '',
            'completed' => (bool) $this->record->is_completed,
        ];

        // Дедлайн
        if ($this->record->deadline) {
            try {
                $payload['due_on'] = Carbon::parse($this->record->deadline)->toDateString();
            } catch (\Exception $e) {
                // Пропускаем некорректную дату
            }
        }

        // Дата начала
        if ($this->record->start_date) {
            try {
                $payload['start_on'] = Carbon::parse($this->record->start_date)->toDateString();
            } catch (\Exception $e) {
                // Пропускаем некорректную дату
            }
        }

        // Исполнитель
        if ($this->record->user && $this->record->user->asana_gid) {
            $payload['assignee'] = $this->record->user->asana_gid;
        }

        // Проект (если задача в проекте) - убираем, так как может быть только при создании
        // if ($this->record->project && $this->record->project->asana_id) {
        //     $payload['projects'] = [$this->record->project->asana_id];
        // }

        // Родительская задача - убираем, так как может быть только при создании
        // if ($this->record->parent && $this->record->parent->gid) {
        //     $payload['parent'] = $this->record->parent->gid;
        // }

        // Кастомные поля
        $customFields = [];

        // Приоритет - отправляем gid опции
        if ($this->record->priority) {
            $priorityMap = [
                'high' => '1202674799522489', // Високий
                'medium' => '1202674799522531', // Средній
                'low' => '1202674799522561', // Низький
            ];
            $priorityGid = $priorityMap[$this->record->priority] ?? '1202674799522561'; // Низький по умолчанию
            $customFields['1202674799521449'] = $priorityGid; // gid поля приоритета
        }

        // Тип задачи - отправляем gid опции
        if ($this->record->status) {
            $statusMap = [
                'new' => '1205860710071792', // Нова функція
                'in_progress' => '1205860710071793', // Покращення
                'needs_clarification' => '1205860710071791', // Помилка сайт
                'completed' => '1205860710071794', // Обслуговування
                'canceled' => '1205860710071794', // Обслуговування
            ];
            $typeGid = $statusMap[$this->record->status] ?? '1205860710071792'; // Нова функція по умолчанию
            $customFields['1205860710071790'] = $typeGid; // gid поля типа задачи
        }

        // Бюджет (часы план)
        if ($this->record->budget) {
            $customFields['1202687202895300'] = (float) $this->record->budget;
        }

        // Витрачено (часы факт)
        if ($this->record->spent) {
            $customFields['1202687202895302'] = (float) $this->record->spent;
        }

        if (! empty($customFields)) {
            $payload['custom_fields'] = $customFields;
        }

        // Убираем пустые значения
        // $payload = array_filter($payload, function ($value) {
        //     return $value !== null && $value !== '' && (! is_array($value) || ! empty($value));
        // });

        Log::info('Sync to Asana payload', [
            'task_id' => $this->record->id,
            'task_gid' => $this->record->gid,
            'payload' => $payload,
            'description_length' => strlen($payload['notes'] ?? ''),
            'record_description' => $this->record->description,
            'record_description_type' => gettype($this->record->description),
        ]);

        try {
            $result = $service->updateTask($this->record->gid, $payload);

            // Перемещаем задачу в секцию на основе статуса
            $this->moveTaskToSectionBasedOnStatus($service);

            Notification::make()
                ->success()
                ->title('Дані відправлені в Asana успішно')
                ->send();

            $this->refresh();
            $this->fillForm($this->record->fresh()->toArray());

            // Синхронизируем комментарии після відправки задачі
            $this->syncCommentsToAsana();
        } catch (AsanaError $e) {
            Notification::make()
                ->danger()
                ->title('Помилка відправки в Asana')
                ->body($e->getMessage())
                ->send();
        }
    }

    private function moveTaskToSectionBasedOnStatus(AsanaService $service): void
    {
        // Находим секцию с соответствующим статусом в том же проекте
        $targetSection = Section::where('project_id', $this->record->project_id)
            ->where('status', $this->record->status)
            ->first();

        if ($targetSection && $targetSection->asana_gid) {
            try {
                // Перемещаем задачу в новую секцию
                $result = $service->moveTaskToSection($this->record->gid, $targetSection->asana_gid);
                Log::info('Asana task moved to section', [
                    'task_id' => $this->record->id,
                    'section_gid' => $targetSection->asana_gid,
                    'result' => $result,
                ]);
            } catch (AsanaError $e) {
                Log::error('Failed to move Asana task to section', [
                    'task_id' => $this->record->id,
                    'section_gid' => $targetSection->asana_gid,
                    'error' => $e->getMessage(),
                ]);
            }
        } else {
            Log::warning('No target section found for task status', [
                'task_id' => $this->record->id,
                'status' => $this->record->status,
                'project_id' => $this->record->project_id,
            ]);
        }
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Если есть новые комментарии, планируем их синхронизацию после сохранения
        $this->pendingComments = [];

        if (isset($data['comments']) && is_array($data['comments'])) {
            foreach ($data['comments'] as $commentData) {
                // Если комментарий новый (нет asana_gid и нет id) и есть content
                if (empty($commentData['asana_gid']) && empty($commentData['id']) && ! empty($commentData['content'])) {
                    $this->pendingComments[] = $commentData['content'];
                }
            }
        }

        return $data;
    }

    protected function afterSave(): void
    {
        // Синхронизируем новые комментарии после сохранения
        if (! empty($this->pendingComments) && $this->record->gid) {
            $this->syncPendingCommentsToAsana();
        }
    }

    private function syncPendingCommentsToAsana(): void
    {
        $service = app(AsanaService::class);

        foreach ($this->pendingComments as $content) {
            try {
                $result = $service->addCommentToTask($this->record->gid, $content);

                Log::info('New comment automatically synced to Asana', [
                    'task_id' => $this->record->id,
                    'comment_gid' => $result['gid'] ?? null,
                ]);

                // Обновляем последний несинхронизированный комментарий с GID
                $comment = $this->record->comments()->whereNull('asana_gid')->latest()->first();
                if ($comment) {
                    $comment->update(['asana_gid' => $result['gid'] ?? null]);
                }

            } catch (\Exception $e) {
                Log::error('Failed to auto-sync comment to Asana', [
                    'task_id' => $this->record->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->pendingComments = [];
    }

    private function syncNewCommentToAsana(string $content): void
    {
        if (! $this->record->gid) {
            return;
        }

        try {
            $service = app(AsanaService::class);
            $result = $service->addCommentToTask($this->record->gid, $content);

            Log::info('Comment synced to Asana', [
                'task_id' => $this->record->id,
                'comment_gid' => $result['gid'] ?? null,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to sync comment to Asana', [
                'task_id' => $this->record->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function refreshTimerData($taskId = null)
    {
        // Если taskId не передан, используем ID текущей записи
        $taskId = $taskId ?? $this->record->id;

        // Обновляем данные задачи из БД с полной загрузкой связей
        $this->record = $this->record->fresh(['times', 'comments']);

        // Перезаполняем форму свежими данными
        $this->fillForm($this->record->toArray());

        // Принудительно обновляем весь компонент страницы
        $this->dispatch('$refresh');

        // Дополнительно обновляем состояние формы
        $this->form->fill($this->record->toArray());

        // Логируем для отладки
        Log::info('Timer data refreshed', [
            'task_id' => $taskId,
            'times_count' => $this->record->times()->count(),
            'comments_count' => $this->record->comments()->count(),
        ]);
    }

    private function createTaskInAsana()
    {
        $service = app(AsanaService::class);
        $payload = [
            'name' => $this->record->title,
            'notes' => $this->record->description ?? '',
        ];

        // Проект (если задача в проекте)
        if ($this->record->project && $this->record->project->asana_id) {
            $payload['projects'] = [$this->record->project->asana_id];
        }

        // Родительская задача
        if ($this->record->parent && $this->record->parent->gid) {
            $payload['parent'] = $this->record->parent->gid;
        }

        // Исполнитель
        if ($this->record->user && $this->record->user->asana_gid) {
            $payload['assignee'] = $this->record->user->asana_gid;
        }

        // Дедлайн
        if ($this->record->deadline) {
            $payload['due_on'] = $this->record->deadline;
        }

        // Дата начала
        if ($this->record->start_date) {
            $payload['start_on'] = $this->record->start_date;
        }

        // Статус завершения
        $payload['completed'] = (bool) ($this->record->is_completed ?? false);

        try {
            $result = $service->createTask($payload);

            if (isset($result['gid'])) {
                // Сохраняем GID в локальную запись задачи без триггера observer'ов
                $this->record->withoutEvents(function () use ($result) {
                    $this->record->update(['gid' => $result['gid']]);
                });

                // Если у задачи есть секция, перемещаем её в нужную секцию
                if ($this->record->section && $this->record->section->asana_gid) {
                    try {
                        $service->moveTaskToSection($result['gid'], $this->record->section->asana_gid);
                        \Log::info('Task moved to section in Asana', [
                            'task_id' => $this->record->id,
                            'gid' => $result['gid'],
                            'section_gid' => $this->record->section->asana_gid,
                        ]);
                    } catch (\Exception $e) {
                        \Log::warning('Failed to move task to section in Asana', [
                            'task_id' => $this->record->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }

                Notification::make()
                    ->success()
                    ->title('Задача створена в Asana')
                    ->body('GID: '.$result['gid'])
                    ->send();

                $this->refresh();
                $this->fillForm($this->record->fresh()->toArray());
            } else {
                Notification::make()
                    ->danger()
                    ->title('Не вдалося отримати GID нової задачі з Asana')
                    ->send();
            }
        } catch (AsanaError $e) {
            Notification::make()
                ->danger()
                ->title('Помилка створення задачі в Asana')
                ->body($e->getMessage())
                ->send();
        }
    }
}
