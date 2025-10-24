<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectCustomField extends Model
{
    protected $fillable = [
        'project_id',
        'asana_gid',
        'name',
        'type',
        'description',
        'enum_options',
        'is_required',
        'precision',
    ];

    protected $casts = [
        'enum_options' => 'array',
        'is_required' => 'boolean',
        'precision' => 'integer',
    ];

    /**
     * Відношення до проєкту.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Відношення до значень поля в тасках.
     */
    public function taskCustomFields(): HasMany
    {
        return $this->hasMany(TaskCustomField::class);
    }

    /**
     * Отримати всі можливі варіанти для enum поля.
     */
    public function getEnumOptions(): array
    {
        if ($this->type !== 'enum' && $this->type !== 'multi_enum') {
            return [];
        }

        return $this->enum_options ?? [];
    }

    /**
     * Знайти варіант enum за GID.
     */
    public function findEnumOption(string $gid): ?array
    {
        $options = $this->getEnumOptions();

        foreach ($options as $option) {
            if (($option['gid'] ?? '') === $gid) {
                return $option;
            }
        }

        return null;
    }
}
