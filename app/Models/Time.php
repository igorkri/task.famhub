<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Time extends Model
{
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
