<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskHistory extends Model
{
    use HasFactory;

    const EVENT_CREATED = 'created';

    const EVENT_UPDATED = 'updated';

    const EVENT_DELETED = 'deleted';

    const EVENT_STATUS_CHANGED = 'status_changed';

    const EVENT_ASSIGNED = 'assigned';

    const EVENT_UNASSIGNED = 'unassigned';

    const EVENT_SECTION_CHANGED = 'section_changed';

    const EVENT_PRIORITY_CHANGED = 'priority_changed';

    const EVENT_DEADLINE_CHANGED = 'deadline_changed';

    const EVENT_COMPLETED = 'completed';

    const EVENT_REOPENED = 'reopened';

    const EVENT_COMMENT_ADDED = 'comment_added';

    const EVENT_ATTACHMENT_ADDED = 'attachment_added';

    const EVENT_CUSTOM_FIELD_CHANGED = 'custom_field_changed';

    const SOURCE_LOCAL = 'local';

    const SOURCE_ASANA_WEBHOOK = 'asana_webhook';

    const SOURCE_ASANA_SYNC = 'asana_sync';

    protected $fillable = [
        'task_id',
        'user_id',
        'event_type',
        'source',
        'field_name',
        'old_value',
        'new_value',
        'changes',
        'metadata',
        'description',
        'event_at',
    ];

    protected $casts = [
        'changes' => 'array',
        'metadata' => 'array',
        'event_at' => 'datetime',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Створити запис історії для зміни поля.
     */
    public static function logFieldChange(
        Task $task,
        string $fieldName,
        mixed $oldValue,
        mixed $newValue,
        string $eventType = self::EVENT_UPDATED,
        string $source = self::SOURCE_LOCAL,
        ?User $user = null,
        ?array $metadata = null
    ): self {
        return self::create([
            'task_id' => $task->id,
            'user_id' => $user?->id ?? auth()->id(),
            'event_type' => $eventType,
            'source' => $source,
            'field_name' => $fieldName,
            'old_value' => is_array($oldValue) ? json_encode($oldValue) : $oldValue,
            'new_value' => is_array($newValue) ? json_encode($newValue) : $newValue,
            'metadata' => $metadata,
            'description' => self::generateDescription($fieldName, $oldValue, $newValue),
        ]);
    }

    /**
     * Створити запис історії для batch змін.
     */
    public static function logBatchChanges(
        Task $task,
        array $changes,
        string $eventType = self::EVENT_UPDATED,
        string $source = self::SOURCE_LOCAL,
        ?User $user = null,
        ?array $metadata = null
    ): self {
        return self::create([
            'task_id' => $task->id,
            'user_id' => $user?->id ?? auth()->id(),
            'event_type' => $eventType,
            'source' => $source,
            'changes' => $changes,
            'metadata' => $metadata,
            'description' => self::generateBatchDescription($changes),
        ]);
    }

    /**
     * Створити запис історії для події без конкретного поля.
     */
    public static function logEvent(
        Task $task,
        string $eventType,
        string $source = self::SOURCE_LOCAL,
        ?string $description = null,
        ?User $user = null,
        ?array $metadata = null
    ): self {
        return self::create([
            'task_id' => $task->id,
            'user_id' => $user?->id ?? auth()->id(),
            'event_type' => $eventType,
            'source' => $source,
            'description' => $description,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Генерувати опис зміни для одного поля.
     */
    protected static function generateDescription(string $fieldName, mixed $oldValue, mixed $newValue): string
    {
        $fieldLabels = [
            'title' => 'Назва',
            'description' => 'Опис',
            'status' => 'Статус',
            'priority' => 'Пріоритет',
            'deadline' => 'Дедлайн',
            'user_id' => 'Виконавець',
            'section_id' => 'Секція',
            'is_completed' => 'Завершено',
            'budget' => 'Бюджет',
            'spent' => 'Витрачено',
            'progress' => 'Прогрес',
        ];

        $label = $fieldLabels[$fieldName] ?? $fieldName;

        if ($oldValue === null) {
            return "Встановлено {$label}: {$newValue}";
        }

        if ($newValue === null) {
            return "Видалено {$label}";
        }

        return "Змінено {$label}: з \"{$oldValue}\" на \"{$newValue}\"";
    }

    /**
     * Генерувати опис для batch змін.
     */
    protected static function generateBatchDescription(array $changes): string
    {
        $count = count($changes);

        if ($count === 1) {
            $field = array_key_first($changes);
            $change = $changes[$field];

            return self::generateDescription($field, $change['old'] ?? null, $change['new'] ?? null);
        }

        return "Оновлено {$count} полів: ".implode(', ', array_keys($changes));
    }

    /**
     * Отримати читабельне значення для статусу.
     */
    public function getReadableOldValueAttribute(): string
    {
        return $this->formatValue($this->field_name, $this->old_value);
    }

    /**
     * Отримати читабельне значення для нового значення.
     */
    public function getReadableNewValueAttribute(): string
    {
        return $this->formatValue($this->field_name, $this->new_value);
    }

    /**
     * Форматувати значення для читабельного відображення.
     */
    protected function formatValue(?string $fieldName, mixed $value): string
    {
        if ($value === null) {
            return '—';
        }

        if ($fieldName === 'status' && isset(Task::$statuses[$value])) {
            return Task::$statuses[$value];
        }

        if ($fieldName === 'priority' && isset(Task::$priorities[$value])) {
            return Task::$priorities[$value];
        }

        if ($fieldName === 'is_completed') {
            return $value ? 'Так' : 'Ні';
        }

        if (in_array($fieldName, ['deadline', 'start_date', 'end_date'])) {
            return \Carbon\Carbon::parse($value)->format('d.m.Y');
        }

        return (string) $value;
    }
}
