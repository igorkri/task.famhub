<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActOfWorkDetail extends Model
{
    /** @use HasFactory<\Database\Factories\ActOfWorkDetailFactory> */
    use HasFactory;

    protected $fillable = [
        'act_of_work_id',
        'time_id',
        'task_gid',
        'project_gid',
        'project',
        'task',
        'description',
        'amount',
        'hours',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'hours' => 'decimal:2',
            'time_id' => 'integer',
        ];
    }

    public function actOfWork(): BelongsTo
    {
        return $this->belongsTo(ActOfWork::class);
    }
}
