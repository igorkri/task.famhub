<?php

namespace App\Livewire;

use App\Models\Task;
use App\Models\Time;
use Filament\Notifications\Notification;
use Livewire\Component;

class QuickTimer extends Component
{
    public ?Time $activeTime = null;

    public int $seconds = 0;

    public bool $isRunning = false;

    public bool $isPaused = false;

    public ?int $timeId = null;

    public bool $isLoading = false;

    public string $title = '';

    public string $description = '';

    public bool $showConvertForm = false;

    public array $availableProjects = [];

    public ?string $selectedProjectId = null;

    public array $availableTasks = [];

    public ?string $selectedTaskId = null;

    protected $listeners = ['timer-tick' => 'incrementTimer'];

    public function mount()
    {
        $this->isLoading = true;
        $this->loadActiveTimer();
        $this->isLoading = false;
    }

    public function loadActiveTimer()
    {
        $this->isLoading = true;

        // Ищем активный таймер без привязки к задаче (quick timer)
        $this->activeTime = Time::where('task_id', null)
            ->where('user_id', auth()->id())
            ->where('status', Time::STATUS_IN_PROGRESS)
            ->first();

        if ($this->activeTime) {
            $this->seconds = $this->activeTime->duration;
            $this->timeId = $this->activeTime->id;
            $this->title = $this->activeTime->title ?? '';
            $this->description = $this->activeTime->description ?? '';
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
            // Создаем новую запись без привязки к задаче
            $this->activeTime = Time::create([
                'task_id' => null, // Быстрый таймер без задачи
                'user_id' => auth()->id(),
                'title' => $this->title ?: 'Швидкий трекінг',
                'description' => $this->description ?: 'Створив: '.(auth()->user()?->name ?? 'Невідомий користувач'),
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
        $this->dispatch('start-quick-timer');
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
                'title' => $this->title ?: $this->activeTime->title,
                'description' => $this->description ?: $this->activeTime->description,
            ]);
        }

        $this->isLoading = false;

        // Останавливаем JavaScript таймер
        $this->dispatch('stop-quick-timer');

        Notification::make()
            ->title('Призупинено!')
            ->success()
            ->send();
    }

    public function stopTimer()
    {
        $this->isLoading = true;

        if ($this->activeTime) {
            $this->activeTime->update([
                'duration' => $this->seconds,
                'status' => Time::STATUS_COMPLETED,
                'title' => $this->title ?: $this->activeTime->title,
                'description' => $this->description ?: $this->activeTime->description,
            ]);
        }

        // Сбрасываем состояние
        $this->reset(['seconds', 'isRunning', 'isPaused', 'timeId', 'activeTime', 'title', 'description']);

        $this->isLoading = false;

        // Останавливаем JavaScript таймер
        $this->dispatch('stop-quick-timer');

        Notification::make()
            ->title('Збережено!')
            ->success()
            ->send();
    }

    public function showConvertToTask()
    {
        if (! $this->activeTime) {
            Notification::make()
                ->title('Помилка')
                ->body('Немає активного таймера для конвертації')
                ->danger()
                ->send();

            return;
        }

        // Загружаем доступные проекты пользователя
        $this->availableProjects = \App\Models\Project::whereHas('tasks', function ($query) {
            $query->where('user_id', auth()->id());
//                ->whereIn('status', [Task::STATUS_NEW, Task::STATUS_IN_PROGRESS]);
        })
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn ($project) => [
                'id' => $project->id,
                'name' => $project->name,
            ])
            ->toArray();

        $this->showConvertForm = true;
    }

    public function updatedSelectedProjectId($projectId)
    {
        if (! $projectId) {
            $this->availableTasks = [];
            $this->selectedTaskId = null;

            return;
        }

        // Загружаем задачи выбранного проекта
        $this->availableTasks = Task::where('user_id', auth()->id())
            ->where('project_id', $projectId)
//            ->whereIn('status', [Task::STATUS_NEW, Task::STATUS_IN_PROGRESS])
            ->orderBy('title')
            ->get()
            ->map(fn ($task) => [
                'id' => $task->id,
                'label' => $task->title,
            ])
            ->toArray();

        // Сбрасываем выбранную задачу
        $this->selectedTaskId = null;
    }

    public function convertToTask()
    {
        if (! $this->selectedTaskId || ! $this->activeTime) {
            Notification::make()
                ->title('Помилка')
                ->body('Виберіть завдання')
                ->danger()
                ->send();

            return;
        }

        $this->isLoading = true;

        // Обновляем запись времени, привязывая её к задаче
        $this->activeTime->update([
            'task_id' => (int) $this->selectedTaskId,
            'duration' => $this->seconds,
            'title' => $this->title ?: $this->activeTime->title,
            'description' => $this->description ?: $this->activeTime->description,
        ]);

        Notification::make()
            ->title('Конвертовано!')
            ->body('Час успішно прив\'язано до завдання')
            ->success()
            ->send();

        // Сбрасываем состояние
        $this->reset(['seconds', 'isRunning', 'isPaused', 'timeId', 'activeTime', 'title', 'description', 'showConvertForm', 'selectedProjectId', 'selectedTaskId', 'availableProjects', 'availableTasks']);

        $this->isLoading = false;

        // Останавливаем JavaScript таймер
        $this->dispatch('stop-quick-timer');
    }

    public function cancelConvert()
    {
        $this->showConvertForm = false;
        $this->selectedProjectId = null;
        $this->selectedTaskId = null;
        $this->availableProjects = [];
        $this->availableTasks = [];
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
        return view('livewire.quick-timer');
    }
}
