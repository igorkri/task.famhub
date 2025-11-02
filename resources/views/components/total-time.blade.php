@php
    $totalSeconds = $times->sum('duration');
    $totalMinutes = floor($totalSeconds / 60);
    $h = str_pad(floor($totalSeconds / 3600), 2, '0', STR_PAD_LEFT);
    $m = str_pad(floor(($totalSeconds % 3600) / 60), 2, '0', STR_PAD_LEFT);
    $s = str_pad($totalSeconds % 60, 2, '0', STR_PAD_LEFT);
    
    // Статистика по статусам
    $plannedTime = $times->where('status', 'planned')->sum('duration');
    $inProgressTime = $times->where('status', 'in_progress')->sum('duration');
    $completedTime = $times->where('status', 'completed')->sum('duration');
    $pausedTime = $times->where('status', 'paused')->sum('duration');
    
    // Конвертируем в часы для отображения
    $formatTime = function($seconds) {
        $h = floor($seconds / 3600);
        $m = floor(($seconds % 3600) / 60);
        return $h > 0 ? "{$h}г {$m}хв" : "{$m}хв";
    };
@endphp

<div class="timer-container">
    <div class="timer-stats-grid">
        <!-- Общее время -->
        <div class="timer-stat-item">
            <div class="timer-main-time">{{ $h }}:{{ $m }}:{{ $s }}</div>
            <div class="timer-label">⏱️ Загальний час</div>
            <div class="timer-sublabel">({{ $totalMinutes }} хвилин)</div>
        </div>
        
        <!-- Количество записей -->
        <div class="timer-stat-item">
            <div class="stat-value-green">{{ $times->count() }}</div>
            <div class="timer-label">📝 Записів</div>
            <div class="timer-sublabel">облік часу</div>
        </div>
        
        <!-- Завершенное время -->
        <div class="timer-stat-item">
            <div class="stat-value-emerald">{{ $formatTime($completedTime) }}</div>
            <div class="timer-label">✅ Завершено</div>
            <div class="timer-sublabel">{{ round(($completedTime / max($totalSeconds, 1)) * 100) }}% від загального</div>
        </div>
        
        <!-- В процессе -->
        <div class="timer-stat-item">
            <div class="stat-value-amber">{{ $formatTime($inProgressTime) }}</div>
            <div class="timer-label">🔄 В процесі</div>
            <div class="timer-sublabel">{{ round(($inProgressTime / max($totalSeconds, 1)) * 100) }}% від загального</div>
        </div>
    </div>
    
    @if($times->count() > 0)
        <!-- Прогресс-бар -->
        <div class="mt-4">
            <div class="text-xs text-gray-600 dark:text-gray-400 mb-2">Розподіл за статусами</div>
            <div class="progress-bar-container">
                @if($completedTime > 0)
                    <div class="progress-emerald" style="width: {{ ($completedTime / $totalSeconds) * 100 }}%" title="Завершено: {{ $formatTime($completedTime) }}"></div>
                @endif
                @if($inProgressTime > 0)
                    <div class="progress-amber" style="width: {{ ($inProgressTime / $totalSeconds) * 100 }}%" title="В процесі: {{ $formatTime($inProgressTime) }}"></div>
                @endif
                @if($plannedTime > 0)
                    <div class="progress-blue" style="width: {{ ($plannedTime / $totalSeconds) * 100 }}%" title="Заплановано: {{ $formatTime($plannedTime) }}"></div>
                @endif
                @if($pausedTime > 0)
                    <div class="progress-gray" style="width: {{ ($pausedTime / $totalSeconds) * 100 }}%" title="На паузі: {{ $formatTime($pausedTime) }}"></div>
                @endif
            </div>
        </div>
        
        <!-- Легенда -->
        <div class="legend-container">
            @if($completedTime > 0)
                <div class="legend-item">
                    <div class="legend-color legend-emerald"></div>
                    <span class="legend-text">Завершено ({{ $formatTime($completedTime) }})</span>
                </div>
            @endif
            @if($inProgressTime > 0)
                <div class="legend-item">
                    <div class="legend-color legend-amber"></div>
                    <span class="legend-text">В процесі ({{ $formatTime($inProgressTime) }})</span>
                </div>
            @endif
            @if($plannedTime > 0)
                <div class="legend-item">
                    <div class="legend-color legend-blue"></div>
                    <span class="legend-text">Заплановано ({{ $formatTime($plannedTime) }})</span>
                </div>
            @endif
            @if($pausedTime > 0)
                <div class="legend-item">
                    <div class="legend-color legend-gray"></div>
                    <span class="legend-text">На паузі ({{ $formatTime($pausedTime) }})</span>
                </div>
            @endif
        </div>
    @else
        <!-- Пустое состояние -->
        <div class="text-center mt-4 py-8">
            <div class="text-4xl mb-2">⏰</div>
            <div class="text-gray-600 dark:text-gray-400 font-medium">Записів часу ще немає</div>
            <div class="text-sm text-gray-500 dark:text-gray-500 mt-1">Додайте перший запис, щоб почати облік часу</div>
        </div>
    @endif
</div>
