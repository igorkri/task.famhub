<?php

namespace App\Filament\Widgets;

use App\Models\Task;
use App\Models\Time;
use Filament\Widgets\Widget;
use Illuminate\Support\Carbon;

class GoalsWidget extends Widget
{
    protected ?string $heading = 'Цілі та прогрес';

    protected static ?int $sort = 4;

    protected string $view = 'filament.widgets.goals-widget';

    protected int|string|array $columnSpan = 'full';

    public function getViewData(): array
    {
        $user = auth()->user();
        $userId = $user->id;
        $now = Carbon::now();

        // Отримуємо налаштування з профілю користувача
        $monthlyHoursGoal = $user->monthly_hours_goal ?? 160;
        $monthlyEarningsGoal = $user->monthly_earnings_goal ?? 64000;
        $weeklyTasksGoal = $user->weekly_tasks_goal ?? 10;
        $hourlyRate = $user->hourly_rate ?? 400;
        $currency = $user->currency ?? 'UAH';
        $rateCoefficient = $user->rate_coefficient ?? 1.0;

        // Фактичні дані за поточний місяць
        $currentMonthTimes = Time::where('user_id', $userId)
            ->whereMonth('created_at', $now->month)
            ->whereYear('created_at', $now->year)
            ->get();

        $actualMonthlyHours = round($currentMonthTimes->sum('duration') / 3600, 1);
        $actualMonthlyEarnings = round($currentMonthTimes->sum('calculated_amount'), 0);

        // Завдання за поточний тиждень
        $weeklyCompletedTasks = Task::where('user_id', $userId)
            ->where('status', Task::STATUS_COMPLETED)
            ->whereBetween('updated_at', [
                $now->startOfWeek(),
                $now->copy()->endOfWeek(),
            ])
            ->count();

        // Денна ціль (робочі дні)
        $workingDaysInMonth = $this->getWorkingDaysInMonth();
        $workingDaysPassed = $this->getWorkingDaysPassed();
        $dailyHoursGoal = $monthlyHoursGoal / max($workingDaysInMonth, 1);
        $expectedHoursToDate = $dailyHoursGoal * $workingDaysPassed;

        // Прогрес
        $hoursProgress = min(100, round(($actualMonthlyHours / max($monthlyHoursGoal, 1)) * 100, 1));
        $earningsProgress = min(100, round(($actualMonthlyEarnings / max($monthlyEarningsGoal, 1)) * 100, 1));
        $tasksProgress = min(100, round(($weeklyCompletedTasks / max($weeklyTasksGoal, 1)) * 100, 1));

        // Статус виконання плану
        $onTrack = $actualMonthlyHours >= $expectedHoursToDate;
        $behindBy = round($expectedHoursToDate - $actualMonthlyHours, 1);

        return [
            'goals' => [
                [
                    'title' => 'Місячний час',
                    'current' => $actualMonthlyHours,
                    'target' => $monthlyHoursGoal,
                    'unit' => 'год.',
                    'progress' => $hoursProgress,
                    'color' => $hoursProgress >= 80 ? 'success' : ($hoursProgress >= 50 ? 'warning' : 'danger'),
                    'icon' => 'heroicon-o-clock',
                ],
                [
                    'title' => 'Місячний заробіток',
                    'current' => number_format($actualMonthlyEarnings, 0, ',', ' '),
                    'target' => number_format($monthlyEarningsGoal, 0, ',', ' '),
                    'unit' => '₴',
                    'progress' => $earningsProgress,
                    'color' => $earningsProgress >= 80 ? 'success' : ($earningsProgress >= 50 ? 'warning' : 'danger'),
                    'icon' => 'heroicon-o-currency-dollar',
                ],
                [
                    'title' => 'Тижневі завдання',
                    'current' => $weeklyCompletedTasks,
                    'target' => $weeklyTasksGoal,
                    'unit' => 'завд.',
                    'progress' => $tasksProgress,
                    'color' => $tasksProgress >= 80 ? 'success' : ($tasksProgress >= 50 ? 'warning' : 'danger'),
                    'icon' => 'heroicon-o-check-circle',
                ],
            ],
            'summary' => [
                'onTrack' => $onTrack,
                'behindBy' => $behindBy,
                'workingDaysPassed' => $workingDaysPassed,
                'workingDaysInMonth' => $workingDaysInMonth,
                'expectedHoursToDate' => round($expectedHoursToDate, 1),
            ],
        ];
    }

    /**
     * Отримати кількість робочих днів у поточному місяці
     */
    protected function getWorkingDaysInMonth(): int
    {
        $now = Carbon::now();
        $start = $now->copy()->startOfMonth();
        $end = $now->copy()->endOfMonth();
        $workingDays = 0;

        while ($start <= $end) {
            if ($start->isWeekday()) {
                $workingDays++;
            }
            $start->addDay();
        }

        return $workingDays;
    }

    /**
     * Отримати кількість робочих днів, які пройшли у поточному місяці
     */
    protected function getWorkingDaysPassed(): int
    {
        $now = Carbon::now();
        $start = $now->copy()->startOfMonth();
        $workingDays = 0;

        while ($start <= $now) {
            if ($start->isWeekday()) {
                $workingDays++;
            }
            $start->addDay();
        }

        return $workingDays;
    }
}
