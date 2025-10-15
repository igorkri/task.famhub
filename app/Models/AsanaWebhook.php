<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AsanaWebhook extends Model
{
    protected $fillable = [
        'gid',
        'resource_type',
        'resource_gid',
        'resource_name',
        'target',
        'active',
        'last_event_at',
        'events_count',
    ];

    protected $casts = [
        'active' => 'boolean',
        'last_event_at' => 'datetime',
        'events_count' => 'integer',
    ];

    /**
     * Increment events count and update last event timestamp.
     */
    public function recordEvent(): void
    {
        $this->increment('events_count');
        $this->update(['last_event_at' => now()]);
    }

    /**
     * Mark webhook as inactive.
     */
    public function deactivate(): void
    {
        $this->update(['active' => false]);
    }

    /**
     * Mark webhook as active.
     */
    public function activate(): void
    {
        $this->update(['active' => true]);
    }
}
