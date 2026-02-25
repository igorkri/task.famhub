<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Модель позиції акту виконаних робіт.
 *
 * @property int $id
 * @property int $contractor_act_of_completed_work_id ID акту
 * @property int $sequence_number № п/п
 * @property string $service_description Опис послуги/роботи
 * @property string $unit Одиниця виміру
 * @property float $quantity Кількість
 * @property float $unit_price Ціна за одиницю
 * @property float $amount Сума по позиції
 * @property int $sort Порядок сортування
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property-read ContractorActOfCompletedWork $actOfWork
 */
class ContractorActOfCompletedWorkItem extends Model
{
    /** @use HasFactory<\Database\Factories\ContractorActOfCompletedWorkItemFactory> */
    use HasFactory;

    protected $table = 'contractor_act_of_completed_work_items';

    protected $fillable = [
        'contractor_act_of_completed_work_id',
        'sequence_number',
        'service_description',
        'unit',
        'quantity',
        'unit_price',
        'amount',
        'sort',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'amount' => 'decimal:2',
            'sort' => 'integer',
        ];
    }

    public function actOfWork(): BelongsTo
    {
        return $this->belongsTo(ContractorActOfCompletedWork::class, 'contractor_act_of_completed_work_id');
    }
}
