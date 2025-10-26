<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Task extends Model
{
    use HasFactory;

    const STATUS_NEW = 'new'; // новий

    const STATUS_IN_PROGRESS = 'in_progress'; // в процесі

    const STATUS_COMPLETED = 'completed'; // виконано

    const STATUS_CANCELED = 'canceled'; // відхилено

    const STATUS_NEEDS_CLARIFICATION = 'needs_clarification'; // потребує уточнення

    // етапи
    const STATUS_ETAP = 'etap'; // етап

    // archived
    const STATUS_ARCHIVED = 'archived'; // архівований

    // ідеї
    const STATUS_IDEA = 'idea'; // ідея

    // other
    const STATUS_OTHER = 'other'; // інше

    const PRIORITY_LOW = 'low';

    const PRIORITY_MEDIUM = 'medium';

    const PRIORITY_HIGH = 'high';

    protected $fillable = [
        'gid',
        'parent_id',
        'project_id',
        'user_id',
        'section_id',
        'title',
        'description',
        'is_completed',
        'status',
        'priority',
        'deadline',
        'budget',
        'spent',
        'progress',
        'start_date',
        'end_date',
        'permalink_url',
    ];

    public static array $statuses = [
        self::STATUS_NEW => 'Новий',
        self::STATUS_IN_PROGRESS => 'В процесі',
        self::STATUS_NEEDS_CLARIFICATION => 'Потребує уточнення',
        self::STATUS_COMPLETED => 'Виконано',
        self::STATUS_CANCELED => 'Відхилено',
        self::STATUS_ETAP => 'Етап',
        self::STATUS_ARCHIVED => 'Архівований',
        self::STATUS_IDEA => 'Ідеї',
        self::STATUS_OTHER => 'Інше',
    ];

    public static array $priorities = [
        self::PRIORITY_LOW => 'Низький',
        self::PRIORITY_MEDIUM => 'Середній',
        self::PRIORITY_HIGH => 'Високий',
    ];

    public function parent()
    {
        return $this->belongsTo(Task::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Task::class, 'parent_id');
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function section()
    {
        return $this->belongsTo(Section::class);
    }

    public function times()
    {
        return $this->hasMany(Time::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(TaskComment::class);
    }

    public function customFields(): HasMany
    {
        return $this->hasMany(TaskCustomField::class);
    }

    public function histories(): HasMany
    {
        return $this->hasMany(TaskHistory::class)->orderBy('event_at', 'desc');
    }

    /**
     * Accessor для отримання посилання на задачу в Asana
     */
    public function getPermalinkUrlAttribute(): ?string
    {
        if (! $this->gid) {
            return null;
        }

        return "https://app.asana.com/0/0/{$this->gid}";
    }

    /**
     * Синхронизировать задачу с Asana (создать, если не существует).
     */
    public function syncToAsana(): void
    {
        // Если задача уже существует в Asana, ничего не делаем
        if ($this->gid) {
            \Log::info('Task already exists in Asana', ['task_id' => $this->id, 'gid' => $this->gid]);

            return;
        }

        // Проверяем, что задача привязана к проекту
        if (! $this->project_id || ! $this->project) {
            \Log::warning('Cannot sync task to Asana: no project', ['task_id' => $this->id]);

            return;
        }

        // Проверяем, что у проекта есть asana_id
        if (! $this->project->asana_id) {
            \Log::warning('Cannot sync task to Asana: project has no asana_id', [
                'task_id' => $this->id,
                'project_id' => $this->project_id,
            ]);

            return;
        }

        $asanaService = app(\App\Services\AsanaService::class);

        // Подготавливаем данные для создания задачи в Asana
        $taskData = [
            'name' => $this->title,
            'notes' => $this->description ?? '',
            'projects' => [$this->project->asana_id],
            'completed' => $this->is_completed ?? false,
        ];

        // Добавляем deadline, если он есть
        if ($this->deadline) {
            $taskData['due_on'] = $this->deadline;
        }

        // Добавляем дату начала, если она есть
        if ($this->start_date) {
            $taskData['start_on'] = $this->start_date;
        }

        // Добавляем исполнителя, если он есть
        if ($this->user && $this->user->asana_gid) {
            $taskData['assignee'] = $this->user->asana_gid;
        }

        try {
            // Создаём задачу в Asana
            $asanaTask = $asanaService->createTask($taskData);

            // Сохраняем gid в нашу базу данных без триггера observer'ов
            $this->withoutEvents(function () use ($asanaTask) {
                $this->gid = $asanaTask['gid'] ?? null;
                $this->save();
            });

            \Log::info('Task created in Asana', [
                'task_id' => $this->id,
                'gid' => $this->gid,
                'asana_task' => $asanaTask,
            ]);

            // Если у задачи есть статус и секция, перемещаем задачу в нужную секцию
            if ($this->status && $this->section && $this->section->asana_gid) {
                $asanaService->moveTaskToSection($this->gid, $this->section->asana_gid);
                \Log::info('Task moved to section in Asana', [
                    'task_id' => $this->id,
                    'section_gid' => $this->section->asana_gid,
                ]);
            }
        } catch (\Exception $e) {
            \Log::error('Failed to create task in Asana', [
                'task_id' => $this->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}
