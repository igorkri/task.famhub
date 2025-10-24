<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ActOfWork extends Model
{
    /** @use HasFactory<\Database\Factories\ActOfWorkFactory> */
    use HasFactory;

    protected $fillable = [
        'number',
        'status',
        'period',
        'period_type',
        'period_year',
        'period_month',
        'user_id',
        'date',
        'description',
        'total_amount',
        'paid_amount',
        'file_excel',
        'sort',
        'telegram_status',
        'type',
    ];

    protected function casts(): array
    {
        return [
            'period' => 'array',
            'date' => 'date',
            'total_amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'sort' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function details(): HasMany
    {
        return $this->hasMany(ActOfWorkDetail::class);
    }
}
