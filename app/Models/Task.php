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
}
