<?php

namespace App\Filament\Widgets;

use App\Models\Project;
use App\Models\Task;
use App\Models\Time;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $userId = auth()->id();

        // Загальна кількість завдань користувача
        $totalTasks = Task::where('user_id', $userId)->count();
        $completedTasks = Task::where('user_id', $userId)
            ->where('status', Task::STATUS_COMPLETED)
            ->count();
        $inProgressTasks = Task::where('user_id', $userId)
            ->where('status', Task::STATUS_IN_PROGRESS)
            ->count();

        // Час за поточний місяць
        $currentMonthTime = Time::where('user_id', $userId)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('duration');
        $currentMonthHours = round($currentMonthTime / 3600, 1);

        // Час за попередній місяць для порівняння
        $lastMonthTime = Time::where('user_id', $userId)
            ->whereMonth('created_at', now()->subMonth()->month)
            ->whereYear('created_at', now()->subMonth()->year)
            ->sum('duration');
        $lastMonthHours = round($lastMonthTime / 3600, 1);

        // Зароблено за поточний місяць
        $earnedThisMonth = Time::where('user_id', $userId)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->get()
            ->sum('calculated_amount');

        // Активні проекти
        $activeProjects = Project::where('is_active', true)->count();

        // Розрахунок тренду часу
        $timeDiff = $currentMonthHours - $lastMonthHours;
        $timeDescription = $timeDiff >= 0
            ? '+'.$timeDiff.' год. від минулого місяця'
            : $timeDiff.' год. від минулого місяця';
        $timeColor = $timeDiff >= 0 ? 'success' : 'danger';

        // Відсоток виконаних завдань
        $completionRate = $totalTasks > 0
            ? round(($completedTasks / $totalTasks) * 100, 1)
            : 0;

        return [
            Stat::make('Усього завдань', $totalTasks)
                ->description($completedTasks.' виконано')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('primary')
                ->chart($this->getTasksTrend()),

            Stat::make('В роботі', $inProgressTasks)
                ->description('Активні завдання')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('warning'),

            Stat::make('Години цього місяця', $currentMonthHours.' год.')
                ->description($timeDescription)
                ->descriptionIcon($timeDiff >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($timeColor)
                ->chart($this->getHoursTrend()),

            Stat::make('Зароблено', number_format($earnedThisMonth, 0, ',', ' ').' ₴')
                ->description('За поточний місяць')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('success'),

            Stat::make('% виконання', $completionRate.'%')
                ->description('Рівень продуктивності')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color($completionRate >= 70 ? 'success' : ($completionRate >= 40 ? 'warning' : 'danger')),

            Stat::make('Активні проекти', $activeProjects)
                ->description('Всього проектів')
                ->descriptionIcon('heroicon-m-folder')
                ->color('info'),
        ];
    }

    /**
     * Отримати тренд завдань за останні 7 днів
     */
    protected function getTasksTrend(): array
    {
        $userId = auth()->id();
        $trend = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $count = Task::where('user_id', $userId)
                ->whereDate('created_at', $date)
                ->count();
            $trend[] = $count;
        }

        return $trend;
    }

    /**
     * Отримати тренд годин за останні 7 днів
     */
    protected function getHoursTrend(): array
    {
        $userId = auth()->id();
        $trend = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $seconds = Time::where('user_id', $userId)
                ->whereDate('created_at', $date)
                ->sum('duration');
            $trend[] = round($seconds / 3600, 1);
        }

        return $trend;
    }
}
