<?php

namespace App\Models\Develop;

use Illuminate\Database\Eloquent\Model;

class DevelopmentPrice extends Model
{
    protected $fillable = [
        'name',
        'price_frontend',
        'avg_hours_frontend',
        'price_backend',
        'avg_hours_backend',
        'currency',
        'description',
    ];

    protected $casts = [
        'price_frontend' => 'decimal:2',
        'avg_hours_frontend' => 'decimal:2',
        'price_backend' => 'decimal:2',
        'avg_hours_backend' => 'decimal:2',
    ];

    // Список валют
    public static function getCurrencyOptions(): array
    {
        return [
            'USD' => 'USD',
            'EUR' => 'EUR',
            'UAH' => 'UAH',
            // Додайте інші валюти за потребою
        ];
    }

}
