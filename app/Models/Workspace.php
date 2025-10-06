<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Workspace extends Model
{
    protected $fillable = ['gid', 'name', 'description'];

    public function projects()
    {
        return $this->hasMany(Project::class);
    }
}
