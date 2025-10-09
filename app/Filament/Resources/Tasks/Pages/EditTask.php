<?php

namespace App\Filament\Resources\Tasks\Pages;

use App\Filament\Resources\Tasks\TaskResource;
use App\Models\Section;
use App\Services\AsanaService;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditTask extends EditRecord
{
    protected static string $resource = TaskResource::class;

    protected static bool $hasStickyFooter = false;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('syncFromAsana')
                ->label('Отримати з Asana')
                ->color('info')
                ->action('syncFromAsana'),
            Action::make('syncToAsana')
                ->label('Відправити в Asana')
                ->color('primary')
                ->action('syncToAsana'),
            $this->getSaveFormAction()
                ->formId('form'),
            DeleteAction::make(),
        ];
    }

    protected function getFormActions(): array
    {
        return [];
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
                $assigneeGid = $data['assignee']->gid ?? null;
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
                    if (isset($membership->section) && $membership->section) {
                        $section = \App\Models\Section::where('asana_gid', $membership->section->gid)->first();
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
                    $fieldGid = $customField->gid ?? null;
                    $value = $customField->enum_value ?? $customField->number_value ?? $customField->text_value ?? null;

                    if ($fieldGid === '1202674799521449' && $value) { // Приоритет
                        $priorityMap = [
                            'Високий' => 'high',
                            'Средній' => 'medium',
                            'Низький' => 'low',
                            'Призупинена' => 'low', // или добавить новый статус
                        ];
                        $updateData['priority'] = $priorityMap[$value->name ?? ''] ?? 'low';
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
                        if ($section && empty($section->status)) {
                            $updateData['status'] = $typeMap[$value->name ?? ''] ?? $updateData['status'] ?? 'new';
                        }
                    }

                    if ($fieldGid === '1202687202895300' && isset($customField->number_value)) { // Бюджет (часы план)
                        $updateData['budget'] = (float) $customField->number_value;
                    }

                    if ($fieldGid === '1202687202895302' && isset($customField->number_value)) { // Витрачено (часы факт)
                        $updateData['spent'] = (float) $customField->number_value;
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

            return;
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

        \Log::info('Sync to Asana payload', [
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
                \Log::info('Asana task moved to section', [
                    'task_id' => $this->record->id,
                    'section_gid' => $targetSection->asana_gid,
                    'result' => $result,
                ]);
            } catch (AsanaError $e) {
                \Log::error('Failed to move Asana task to section', [
                    'task_id' => $this->record->id,
                    'section_gid' => $targetSection->asana_gid,
                    'error' => $e->getMessage(),
                ]);
            }
        } else {
            \Log::warning('No target section found for task status', [
                'task_id' => $this->record->id,
                'status' => $this->record->status,
                'project_id' => $this->record->project_id,
            ]);
        }
    }
}
