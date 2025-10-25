<?php

namespace App\Filament\Resources\ActOfWorks\Widgets;

use App\Models\ActOfWork;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use App\Models\Time;

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
            ->where('period_type', '!=', 'new_project')
            ->first();

        $totalAmount = $stats->total_amount ?? 0;
        $paidAmount = $stats->paid_amount ?? 0;
        $debtAmount = $totalAmount - $paidAmount;

        // Получаем данные для графиков за последние 6 месяцев
        $chartData = ActOfWork::query()
            ->selectRaw('
                DATE_FORMAT(date, "%Y-%m") as month,
                SUM(total_amount) as total,
                SUM(paid_amount) as paid
            ')
            ->where('period_type', '!=', 'new_project')
            ->where('date', '>=', now()->subMonths(6))
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        $totalChart = $chartData->pluck('total')->map(fn($val) => (float) $val)->toArray();
        $paidChart = $chartData->pluck('paid')->map(fn($val) => (float) $val)->toArray();
        
        // Для графика долга
        $debtChart = $chartData->map(function($item) {
            return (float) ($item->total - $item->paid);
        })->toArray();

        // Получаем сумму из таймера (duration * coefficient / 3600 для перевода в часы)
        // или используем сумму из act_of_work_details
        // $timerTotal = DB::table('act_of_work_details')
            // ->sum('amount') ?? 0;

            //$timers = Timer::find()
            // ->where(['status' => [Timer::STATUS_WAIT, Timer::STATUS_PROCESS, Timer::STATUS_PLANNED, Timer::STATUS_NEED_CLARIFICATION]])
            // ->andWhere(['status_act' => Timer::STATUS_ACT_NOT_OK])
            // ->andWhere(['not', ['archive' => Timer::ARCHIVE_YES]])
            // ->all();

        // $timerTotalPrice = 0;
        // if (!empty($timers)) {
        //     foreach ($timers as $timer) {
        //         /** @var $timer Timer */
        //         $timerTotalPrice += $timer->getTotalPrice();
        //     }
        // }

        $timerTotal = DB::table('times')
            ->selectRaw('SUM((duration / 3600) * coefficient * ?) as total', [\App\Models\Time::PRICE])
            ->where('is_archived', false)
            ->whereIn('status', [Time::STATUS_NEW, Time::STATUS_IN_PROGRESS, Time::STATUS_PLANNED, Time::STATUS_NEEDS_CLARIFICATION, Time::STATUS_COMPLETED])
            ->value('total') ?? 0;

        // График для таймера за последние 6 месяцев
        $timerChart = DB::table('times')
            ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, SUM((duration / 3600) * coefficient * ?) as total', [\App\Models\Time::PRICE])
            ->where('is_archived', false)
            ->whereIn('status', [Time::STATUS_NEW, Time::STATUS_IN_PROGRESS, Time::STATUS_PLANNED, Time::STATUS_NEEDS_CLARIFICATION, Time::STATUS_COMPLETED])
            ->where('created_at', '>=', now()->subMonths(6))
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('total')
            ->map(fn($val) => (float) $val)
            ->toArray();

        return [
            Stat::make('Загальна сума', number_format($totalAmount, 2, ',', ' ') . ' ₴')
                ->chart($totalChart ?: [0])
                ->description('Всього по актам (без нових проєктів)')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('primary'),

            Stat::make('Оплачено', number_format($paidAmount, 2, ',', ' ') . ' ₴')
                ->chart($paidChart ?: [0])
                ->description('Сплачено')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Сума боргу', number_format($debtAmount, 2, ',', ' ') . ' ₴')
                ->chart($debtChart ?: [0])
                ->description($debtAmount > 0 ? 'До оплати' : 'Переплата')
                ->descriptionIcon($debtAmount > 0 ? 'heroicon-m-exclamation-circle' : 'heroicon-m-check-badge')
                ->color($debtAmount > 0 ? 'warning' : 'success'),

            Stat::make('Зароблено (Timer)', number_format($timerTotal, 2, ',', ' ') . ' ₴')
                ->chart($timerChart ?: [0])
                ->description('По таймеру задач')
                ->descriptionIcon('heroicon-m-clock')
                ->color('info'),
        ];
    }
}

