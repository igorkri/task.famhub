<?php

namespace App\Livewire;

use App\Models\Task;
use App\Models\Time;
use Filament\Notifications\Notification;
use Livewire\Component;

class TaskTimer extends Component
{
    public Task $task;

    public ?Time $activeTime = null;

    public int $seconds = 0;

    public bool $isRunning = false;

    public bool $isPaused = false;

    public ?int $timeId = null;

    public bool $isLoading = false;

    protected $listeners = ['timer-tick' => 'incrementTimer'];

    public function mount(Task $task)
    {
        $this->task = $task;
        $this->isLoading = true;
        $this->loadActiveTimer();
        $this->isLoading = false;
    }

    public function loadActiveTimer()
    {
        $this->isLoading = true;

        $this->activeTime = Time::where('task_id', $this->task->id)
            ->where('user_id', auth()->id())
            ->where('status', Time::STATUS_IN_PROGRESS)
            ->first();

        if ($this->activeTime) {
            $this->seconds = $this->activeTime->duration;
            $this->timeId = $this->activeTime->id;
            $this->isPaused = true; // Считаем что на паузе после загрузки
        }

        $this->isLoading = false;
    }

    public function startTimer()
    {
        $this->isLoading = true;

        if ($this->isPaused && $this->activeTime) {
            // Продолжаем существующую запись
            $this->isPaused = false;
            $this->isRunning = true;
        } else {
            // Создаем новую запись
            $this->activeTime = Time::create([
                'task_id' => $this->task->id,
                'user_id' => auth()->id(),
                'title' => 'Створено автоматично',
                'description' => 'Створив: '.auth()->user()?->name ?? 'Невідомий користувач',
                'coefficient' => Time::COEFFICIENT_STANDARD,
                'duration' => 0,
                'status' => Time::STATUS_IN_PROGRESS,
                'report_status' => 'not_submitted',
                'is_archived' => false,
            ]);

            $this->timeId = $this->activeTime->id;
            $this->seconds = 0;
            $this->isRunning = true;
            $this->isPaused = false;
        }

        $this->isLoading = false;

        // Запускаем JavaScript таймер
        $this->dispatch('start-timer');
    }

    public function pauseTimer()
    {
        $this->isLoading = true;

        $this->isPaused = true;
        $this->isRunning = false;

        // Сохраняем текущее время
        if ($this->activeTime) {
            $this->activeTime->update([
                'duration' => $this->seconds,
            ]);
        }

        $this->isLoading = false;

        // Останавливаем JavaScript таймер
        $this->dispatch('stop-timer');

        Notification::make()
            ->title('Призупинено!')
            ->success()
            ->send();
        $this->dispatch('refreshComponent')->to('app.filament.resources.tasks.pages.edit-task');
    }

    public function stopTimer()
    {
        $this->isLoading = true;

        if ($this->activeTime) {
            $this->activeTime->update([
                'duration' => $this->seconds,
                'status' => Time::STATUS_COMPLETED,
            ]);
        }

        // Сбрасываем состояние
        $this->reset(['seconds', 'isRunning', 'isPaused', 'timeId', 'activeTime']);

        $this->isLoading = false;

        // Останавливаем JavaScript таймер
        $this->dispatch('stop-timer');

        Notification::make()
            ->title('Збережено!')
            ->success()
            ->send();

        // Обновляем другие компоненты без перезагрузки страницы
        $this->dispatch('timer-stopped', taskId: $this->task->id);

        // Обновляем repeater с записями времени на той же странице
        $this->dispatch('refreshComponent')->to('app.filament.resources.tasks.pages.edit-task');
    }

    public function incrementTimer()
    {
        if ($this->isRunning && ! $this->isPaused) {
            $this->seconds++;

            // Автосохранение каждую минуту
            if ($this->seconds % 60 === 0 && $this->activeTime) {
                $this->activeTime->update([
                    'duration' => $this->seconds,
                ]);
            }
        }
    }

    public function getFormattedTimeProperty()
    {
        $hours = str_pad(floor($this->seconds / 3600), 2, '0', STR_PAD_LEFT);
        $minutes = str_pad(floor(($this->seconds % 3600) / 60), 2, '0', STR_PAD_LEFT);
        $seconds = str_pad($this->seconds % 60, 2, '0', STR_PAD_LEFT);

        return "{$hours}:{$minutes}:{$seconds}";
    }

    public function render()
    {
        return view('livewire.task-timer');
    }
}
