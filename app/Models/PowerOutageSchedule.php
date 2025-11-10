<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PowerOutageSchedule extends Model
{
    /** @use HasFactory<\Database\Factories\PowerOutageScheduleFactory> */
    use HasFactory;

    protected $fillable = [
        'schedule_date',
        'description',
        'periods',
        'schedule_data',
        'fetched_at',
        'hash',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'schedule_date' => 'date',
            'periods' => 'array',
            'schedule_data' => 'array',
            'fetched_at' => 'datetime',
            'metadata' => 'array',
        ];
    }
}
