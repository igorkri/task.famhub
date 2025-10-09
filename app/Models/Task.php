<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    const STATUS_NEW = 'new'; // новий

    const STATUS_IN_PROGRESS = 'in_progress'; // в процесі

    const STATUS_COMPLETED = 'completed'; // виконано

    const STATUS_CANCELED = 'canceled'; // відхилено

    const STATUS_NEEDS_CLARIFICATION = 'needs_clarification'; // потребує уточнення

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
    ];

    public static array $statuses = [
        self::STATUS_NEW => 'Новий',
        self::STATUS_IN_PROGRESS => 'В процесі',
        self::STATUS_NEEDS_CLARIFICATION => 'Потребує уточнення',
        self::STATUS_COMPLETED => 'Виконано',
        self::STATUS_CANCELED => 'Відхилено',
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
}
