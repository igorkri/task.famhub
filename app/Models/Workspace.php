<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Workspace extends Model
{
    protected $fillable = ['name', 'description'];

    public function projects()
    {
        return $this->hasMany(Project::class);
    }
}
