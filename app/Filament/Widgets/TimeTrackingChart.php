<?php

namespace App\Filament\Widgets;

use App\Models\Time;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class TimeTrackingChart extends ChartWidget
{
    protected ?string $heading = 'Відстежений час (останні 14 днів)';

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    protected ?string $maxHeight = '300px';

    protected function getData(): array
    {
        $userId = auth()->id();
        $days = 14;

        $labels = [];
        $hoursData = [];
        $earningsData = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $labels[] = $date->format('d.m');

            $dayTimes = Time::where('user_id', $userId)
                ->whereDate('created_at', $date)
                ->get();

            $totalSeconds = $dayTimes->sum('duration');
            $hoursData[] = round($totalSeconds / 3600, 2);

            $totalEarnings = $dayTimes->sum('calculated_amount');
            $earningsData[] = round($totalEarnings, 0);
        }

        return [
            'datasets' => [
                [
                    'label' => 'Години',
                    'data' => $hoursData,
                    'backgroundColor' => 'rgba(99, 102, 241, 0.2)',
                    'borderColor' => '#6366f1',
                    'borderWidth' => 2,
                    'fill' => true,
                    'tension' => 0.3,
                    'yAxisID' => 'y',
                ],
                [
                    'label' => 'Заробіток (₴)',
                    'data' => $earningsData,
                    'backgroundColor' => 'rgba(16, 185, 129, 0.2)',
                    'borderColor' => '#10b981',
                    'borderWidth' => 2,
                    'fill' => true,
                    'tension' => 0.3,
                    'yAxisID' => 'y1',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => [
                    'type' => 'linear',
                    'display' => true,
                    'position' => 'left',
                    'title' => [
                        'display' => true,
                        'text' => 'Години',
                    ],
                ],
                'y1' => [
                    'type' => 'linear',
                    'display' => true,
                    'position' => 'right',
                    'title' => [
                        'display' => true,
                        'text' => 'Заробіток (₴)',
                    ],
                    'grid' => [
                        'drawOnChartArea' => false,
                    ],
                ],
            ],
            'plugins' => [
                'legend' => [
                    'position' => 'top',
                ],
            ],
            'maintainAspectRatio' => false,
        ];
    }
}
