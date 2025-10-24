<?php

namespace App\Filament\Resources\ActOfWorks\Widgets;

use App\Models\ActOfWork;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class ActOfWorkStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        // Получаем суммы из актов работ
        $stats = ActOfWork::query()
            ->selectRaw('
                SUM(total_amount) as total_amount,
                SUM(paid_amount) as paid_amount
            ')
            ->first();

        $totalAmount = $stats->total_amount ?? 0;
        $paidAmount = $stats->paid_amount ?? 0;
        $debtAmount = $totalAmount - $paidAmount;

        // Получаем сумму из таймера (duration * coefficient / 3600 для перевода в часы)
        // или используем сумму из act_of_work_details
        $timerTotal = DB::table('act_of_work_details')
            ->sum('amount') ?? 0;

        return [
            Stat::make('Загальна сума', number_format($totalAmount, 2, ',', ' ') . ' ₴')
                ->description('Всього по актам')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('primary'),

            Stat::make('Оплачено', number_format($paidAmount, 2, ',', ' ') . ' ₴')
                ->description('Сплачено')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Сума боргу', number_format($debtAmount, 2, ',', ' ') . ' ₴')
                ->description($debtAmount > 0 ? 'До оплати' : 'Переплата')
                ->descriptionIcon($debtAmount > 0 ? 'heroicon-m-exclamation-circle' : 'heroicon-m-check-badge')
                ->color($debtAmount > 0 ? 'warning' : 'success'),

            Stat::make('Зароблено (Timer)', number_format($timerTotal, 2, ',', ' ') . ' ₴')
                ->description('По таймеру задач')
                ->descriptionIcon('heroicon-m-clock')
                ->color('info'),
        ];
    }
}

