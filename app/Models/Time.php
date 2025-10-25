<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Time
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
 */
class Time extends Model
{
    use HasFactory;

    const COEFFICIENT_STANDARD = 1.2; // стандартний коефіцієнт

    const PRICE = 400; // базова погодинна ставка в грн

    // заплановано, в процесі, завершено, скасовано, експорт акту, потребує уточнення
    const STATUS_NEW = 'new'; // новий

    const STATUS_IN_PROGRESS = 'in_progress'; // в процесі

    const STATUS_COMPLETED = 'completed'; // виконано

    const STATUS_CANCELED = 'canceled'; // відхилено

    const STATUS_NEEDS_CLARIFICATION = 'needs_clarification'; // потребує уточнення

    const STATUS_PLANNED = 'planned'; // заплановано

    const STATUS_EXPORT_AKT = 'export_akt'; // експорт акту

    public $calculated_amount;

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

    protected $casts = [
        'is_archived' => 'boolean',
        'duration' => 'integer',
        'coefficient' => 'float',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'description' => 'string',
        'title' => 'string',
        'status' => 'string',
        'report_status' => 'string',
    ];

    protected $appends = [
        'calculated_amount',
    ];

    public function getCalculatedAmountAttribute()
    {
        return (($this->duration / 3600) * $this->coefficient) * self::PRICE;
    }

    public static array $statuses = [
        self::STATUS_NEW => 'Новий',
        self::STATUS_IN_PROGRESS => 'В процесі',
        self::STATUS_NEEDS_CLARIFICATION => 'Потребує уточнення',
        self::STATUS_COMPLETED => 'Виконано',
        self::STATUS_CANCELED => 'Відхилено',
        self::STATUS_PLANNED => 'Заплановано',
        self::STATUS_EXPORT_AKT => 'Експортовано в акти',
    ];

    // Коефіцієнти для різних типів робіт
    public static array $coefficients = [
        '2.00' => '2.0',
        '1.80' => '1.8',
        '1.70' => '1.7',
        '1.60' => '1.6',
        '1.50' => '1.5',
        '1.30' => '1.3',
        '1.20' => '1.2 (14.03.2025)',
        '1.10' => '1.1',
        '1.00' => '1.0',
        '0.80' => '0.8',
        '0.50' => '0.5',
        '0.30' => '0.3',
        '0.10' => '0.1',
        '0.00' => '0.0',
    ];

    public static array $reportStatuses = [
        'not_submitted' => 'Не подано',
        'submitted' => 'Подано',
        'approved' => 'Затверджено',
        'rejected' => 'Відхилено',
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

    /**
     * Set the duration attribute.
     * Accepts input in seconds (integer) or "HH:MM:SS" format.
     *
     * @return void
     */
    public function setDurationAttribute($value)
    {
        if (is_numeric($value)) {
            $this->attributes['duration'] = $value;

            return;
        }
        if (preg_match('/^(\d+):(\d{2}):(\d{2})$/', $value, $matches)) {
            $this->attributes['duration'] = $matches[1] * 3600 + $matches[2] * 60 + $matches[3];
        } else {
            $this->attributes['duration'] = (int) $value;
        }
    }

    /**
     * Get the duration attribute in "HH:MM:SS" format for forms.
     *
     * @return string
     */
    public function getDurationForFormAttribute()
    {
        $seconds = $this->duration ?? 0;
        $h = str_pad(floor($seconds / 3600), 2, '0', STR_PAD_LEFT);
        $m = str_pad(floor(($seconds % 3600) / 60), 2, '0', STR_PAD_LEFT);
        $s = str_pad($seconds % 60, 2, '0', STR_PAD_LEFT);

        return "{$h}:{$m}:{$s}";
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
