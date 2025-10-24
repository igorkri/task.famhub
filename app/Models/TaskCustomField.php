<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskCustomField extends Model
{
    protected $fillable = [
        'task_id',
        'asana_gid',
        'name',
        'type',
        'text_value',
        'number_value',
        'date_value',
        'enum_value_gid',
        'enum_value_name',
        'multi_enum_values',
    ];

    protected $casts = [
        'number_value' => 'decimal:2',
        'date_value' => 'date',
        'multi_enum_values' => 'array',
    ];

    /**
     * Відношення до таску.
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    /**
     * Отримати значення поля в зрозумілому форматі.
     */
    public function getValue(): mixed
    {
        return match ($this->type) {
            'text' => $this->text_value,
            'number' => $this->number_value,
            'date' => $this->date_value?->format('d.m.Y'),
            'enum' => $this->enum_value_name,
            'multi_enum' => $this->multi_enum_values,
            default => null,
        };
    }

    /**
     * Встановити значення поля з даних Asana.
     */
    public function setValueFromAsana(array $fieldData): void
    {
        $this->type = $fieldData['type'] ?? 'text';
        $this->text_value = $fieldData['text_value'] ?? null;
        $this->number_value = $fieldData['number_value'] ?? null;
        $this->date_value = $fieldData['date_value'] ?? null;

        if (isset($fieldData['enum_value']) && is_array($fieldData['enum_value'])) {
            $this->enum_value_gid = $fieldData['enum_value']['gid'] ?? null;
            $this->enum_value_name = $fieldData['enum_value']['name'] ?? null;
        }

        if (isset($fieldData['multi_enum_values']) && is_array($fieldData['multi_enum_values'])) {
            $this->multi_enum_values = $fieldData['multi_enum_values'];
        }
    }
}
