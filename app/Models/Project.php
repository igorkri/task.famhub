<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    protected $fillable = ['asana_id', 'name', 'description', 'workspace_id'];

    public function workspace()
    {
        return $this->belongsTo(Workspace::class);
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    public function customFields(): HasMany
    {
        return $this->hasMany(ProjectCustomField::class);
    }
}
