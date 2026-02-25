<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Модель акту виконаних робіт підрядника.
 *
 * @property int $id
 * @property string $number Номер акту
 * @property \Carbon\Carbon $date Дата складання акту
 * @property string|null $place_of_compilation Місце складання
 * @property int $contractor_id ID підрядника
 * @property int|null $customer_id ID замовника (контрагент)
 * @property string|null $agreement_number Номер договору
 * @property \Carbon\Carbon|null $agreement_date Дата договору
 * @property array|null $customer_data Дані замовника
 * @property float $total_amount Загальна сума
 * @property float $vat_amount Сума ПДВ
 * @property float $total_with_vat Загальна сума з ПДВ
 * @property string|null $total_amount_in_words Сума прописом
 * @property string|null $description Опис / Примітки
 * @property string $status Статус акту
 * @property array|null $files Файли акту
 * @property int $sort Порядок сортування
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property-read Contractor $contractor
 * @property-read Contractor|null $customer
 * @property-read \Illuminate\Database\Eloquent\Collection|ContractorActOfCompletedWorkItem[] $items
 * @property-read int|null $items_count
 */
class ContractorActOfCompletedWork extends Model
{
    /** @use HasFactory<\Database\Factories\ContractorActOfCompletedWorkFactory> */
    use HasFactory;

    protected $table = 'contractor_acts_of_completed_works';

    const STATUS_DRAFT = 'draft';
    const STATUS_SIGNED = 'signed';
    const STATUS_PAID = 'paid';
    const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'number',
        'date',
        'place_of_compilation',
        'contractor_id',
        'customer_id',
        'agreement_number',
        'agreement_date',
        'customer_data',
        'total_amount',
        'vat_amount',
        'total_with_vat',
        'total_amount_in_words',
        'description',
        'status',
        'files',
        'sort',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'agreement_date' => 'date',
            'customer_data' => 'array',
            'total_amount' => 'decimal:2',
            'vat_amount' => 'decimal:2',
            'total_with_vat' => 'decimal:2',
            'files' => 'array',
            'sort' => 'integer',
        ];
    }

    public static array $statusList = [
        self::STATUS_DRAFT => 'Чернетка',
        self::STATUS_SIGNED => 'Підписано',
        self::STATUS_PAID => 'Оплачено',
        self::STATUS_CANCELLED => 'Скасовано',
    ];

    public function contractor(): BelongsTo
    {
        return $this->belongsTo(Contractor::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Contractor::class, 'customer_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ContractorActOfCompletedWorkItem::class, 'contractor_act_of_completed_work_id');
    }
}
