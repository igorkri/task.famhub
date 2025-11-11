<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AirAlert extends Model
{
    protected $fillable = [
        'region_id',
        'region_name',
        'is_active',
        'alert_type',
        'started_at',
        'ended_at',
        'duration_minutes',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    /**
     * Отримати активні тривоги
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->whereNull('ended_at');
    }

    /**
     * Отримати історію для регіону
     */
    public function scopeForRegion($query, string $regionId)
    {
        return $query->where('region_id', $regionId);
    }

    /**
     * Розрахувати тривалість тривоги
     */
    public function calculateDuration(): void
    {
        if ($this->started_at && $this->ended_at) {
            $this->duration_minutes = $this->started_at->diffInMinutes($this->ended_at);
            $this->save();
        }
    }
}
