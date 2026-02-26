<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Модель підрядника (контрагента).
 *
 * @property int $id
 * @property int $sort
 * @property string $name
 * @property string|null $phone
 * @property string|null $email
 * @property string $type fop|tov
 * @property string|null $full_name
 * @property string|null $in_the_person_of
 * @property bool $is_active
 * @property bool $my_company
 * @property string|null $description
 * @property array|null $dogovor
 * @property array|null $dogovor_files
 * @property array|null $requisites
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 */
class Contractor extends Model
{
    /** @use HasFactory<\Database\Factories\ContractorFactory> */
    use HasFactory;

    const TYPE_FOP = 'fop';
    const TYPE_TOV = 'tov';

    protected $fillable = [
        'sort',
        'name',
        'phone',
        'email',
        'type',
        'full_name',
        'in_the_person_of',
        'is_active',
        'my_company',
        'description',
        'dogovor',
        'dogovor_files',
        'requisites',
    ];

    public static function typeList(): array
    {
        return self::$typeList;
    }

    public static function myCompany()
    {
        return self::where('my_company', true)->first();
    }

    protected function casts(): array
    {
        return [
            'sort' => 'integer',
            'is_active' => 'boolean',
            'my_company' => 'boolean',
            'dogovor' => 'array',
            'dogovor_files' => 'array',
            'requisites' => 'array',
        ];
    }

    public static array $typeList = [
        self::TYPE_FOP => 'ФОП',
        self::TYPE_TOV => 'ТОВ',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeMyCompany($query)
    {
        return $query->where('my_company', true);
    }

    public function scopeNotMyCompany($query)
    {
        return $query->where('my_company', false);
    }


}
