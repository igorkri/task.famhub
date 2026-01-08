<?php

namespace App\Filament\Widgets;

use App\Models\Task;
use Filament\Widgets\ChartWidget;

class TasksByStatusChart extends ChartWidget
{
    protected ?string $heading = 'Завдання по статусах';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected ?string $maxHeight = '300px';

    protected function getData(): array
    {
        $userId = auth()->id();

        $statusCounts = [];
        $labels = [];
        $colors = [];

        $colorMap = [
            Task::STATUS_NEW => '#6366f1',           // indigo
            Task::STATUS_IN_PROGRESS => '#f59e0b',   // amber
            Task::STATUS_COMPLETED => '#10b981',     // emerald
            Task::STATUS_CANCELED => '#ef4444',      // red
            Task::STATUS_NEEDS_CLARIFICATION => '#8b5cf6', // violet
            Task::STATUS_ETAP => '#06b6d4',          // cyan
            Task::STATUS_ARCHIVED => '#64748b',      // slate
            Task::STATUS_IDEA => '#ec4899',          // pink
            Task::STATUS_OTHER => '#78716c',         // stone
        ];

        foreach (Task::$statuses as $status => $label) {
            $count = Task::where('user_id', $userId)
                ->where('status', $status)
                ->count();

            if ($count > 0) {
                $statusCounts[] = $count;
                $labels[] = $label;
                $colors[] = $colorMap[$status] ?? '#64748b';
            }
        }

        return [
            'datasets' => [
                [
                    'label' => 'Завдання',
                    'data' => $statusCounts,
                    'backgroundColor' => $colors,
                    'borderColor' => '#ffffff',
                    'borderWidth' => 2,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'position' => 'right',
                    'labels' => [
                        'padding' => 20,
                        'usePointStyle' => true,
                    ],
                ],
            ],
            'maintainAspectRatio' => false,
        ];
    }
}
