<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Workspace extends Model
{
    use HasFactory;

    protected $fillable = ['gid', 'name', 'description'];

    public function projects()
    {
        return $this->hasMany(Project::class);
    }
}
