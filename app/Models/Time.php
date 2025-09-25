<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Time
 * @package App\Models
 *
 * @property int $id
 * @property int $task_id
 * @property int $user_id
 * @property string $title
 * @property string|null $description
 * @property float $coefficient
 * @property int $duration // in seconds
 * @property string $status
 * @property string $report_status
 * @property bool $is_archived
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 */
class Time extends Model
{
    // заплановано, в процесі, завершено, скасовано
    const STATUS_NEW = 'new'; // новий
    const STATUS_IN_PROGRESS = 'in_progress'; // в процесі
    const STATUS_COMPLETED = 'completed'; // виконано
    const STATUS_CANCELED = 'canceled'; // відхилено
    const STATUS_NEEDS_CLARIFICATION = 'needs_clarification'; // потребує уточнення
    const STATUS_PLANNED = 'planned'; // заплановано

    protected $fillable = [
        'task_id',
        'user_id',
        'title',
        'description',
        'coefficient',
        'duration',
        'status',
        'report_status',
        'is_archived',
    ];

    public static array $statuses = [
        self::STATUS_NEW                    => 'Новий',
        self::STATUS_IN_PROGRESS            => 'В процесі',
        self::STATUS_NEEDS_CLARIFICATION    => 'Потребує уточнення',
        self::STATUS_COMPLETED              => 'Виконано',
        self::STATUS_CANCELED               => 'Відхилено',
        self::STATUS_PLANNED                => 'Заплановано',
    ];

    public static array $reportStatuses = [
        'not_submitted' => 'Не подано',
        'submitted'     => 'Подано',
        'approved'      => 'Затверджено',
        'rejected'      => 'Відхилено',
    ];


    // Define relationships if needed
    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Additional methods or accessors can be added here
    public function getDurationInHoursAttribute()
    {
        return $this->duration / 3600; // Convert seconds to hours
    }

    public function getDurationInMinutesAttribute()
    {
        return $this->duration / 60; // Convert seconds to minutes
    }

    public function getDurationInSecondsAttribute()
    {
        return $this->duration; // Duration in seconds
    }
}
