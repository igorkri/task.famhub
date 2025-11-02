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

<div class="bg-gradient-to-br from-emerald-50 to-teal-50 dark:from-emerald-950/20 dark:to-teal-950/20 rounded-xl p-6 border border-emerald-200 dark:border-emerald-800 shadow-sm">
    @if($times->count() > 0)
        <!-- Статистика -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <!-- Общее время -->
            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 text-center shadow-sm border border-emerald-100 dark:border-emerald-900">
                <div class="text-3xl font-bold text-emerald-600 dark:text-emerald-400 mb-1">{{ $h }}:{{ $m }}:{{ $s }}</div>
                <div class="text-sm font-medium text-gray-700 dark:text-gray-300 flex items-center justify-center gap-1">
                    <span>⏱️</span>
                    <span>Загальний час</span>
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ number_format($totalMinutes, 0) }} хвилин</div>
            </div>
            
            <!-- Количество записей -->
            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 text-center shadow-sm border border-blue-100 dark:border-blue-900">
                <div class="text-3xl font-bold text-blue-600 dark:text-blue-400 mb-1">{{ $times->count() }}</div>
                <div class="text-sm font-medium text-gray-700 dark:text-gray-300 flex items-center justify-center gap-1">
                    <span>📝</span>
                    <span>Записів</span>
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">облік часу</div>
            </div>
            
            <!-- Завершенное время -->
            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 text-center shadow-sm border border-green-100 dark:border-green-900">
                <div class="text-3xl font-bold text-green-600 dark:text-green-400 mb-1">{{ $formatTime($completedTime) }}</div>
                <div class="text-sm font-medium text-gray-700 dark:text-gray-300 flex items-center justify-center gap-1">
                    <span>✅</span>
                    <span>Завершено</span>
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ round(($completedTime / max($totalSeconds, 1)) * 100) }}% від загального</div>
            </div>
            
            <!-- В процессе -->
            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 text-center shadow-sm border border-amber-100 dark:border-amber-900">
                <div class="text-3xl font-bold text-amber-600 dark:text-amber-400 mb-1">{{ $formatTime($inProgressTime) }}</div>
                <div class="text-sm font-medium text-gray-700 dark:text-gray-300 flex items-center justify-center gap-1">
                    <span>🔄</span>
                    <span>В процесі</span>
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ round(($inProgressTime / max($totalSeconds, 1)) * 100) }}% від загального</div>
            </div>
        </div>
        
        <!-- Прогресс-бар -->
        <div class="mb-4">
            <div class="text-xs font-medium text-gray-600 dark:text-gray-400 mb-2 flex items-center gap-1">
                <span>📊</span>
                <span>Розподіл за статусами</span>
            </div>
            <div class="flex h-3 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden shadow-inner">
                @if($completedTime > 0)
                    <div class="bg-green-500 dark:bg-green-600 hover:bg-green-600 dark:hover:bg-green-700 transition-colors" 
                         style="width: {{ ($completedTime / $totalSeconds) * 100 }}%" 
                         title="Завершено: {{ $formatTime($completedTime) }}">
                    </div>
                @endif
                @if($inProgressTime > 0)
                    <div class="bg-amber-500 dark:bg-amber-600 hover:bg-amber-600 dark:hover:bg-amber-700 transition-colors" 
                         style="width: {{ ($inProgressTime / $totalSeconds) * 100 }}%" 
                         title="В процесі: {{ $formatTime($inProgressTime) }}">
                    </div>
                @endif
                @if($plannedTime > 0)
                    <div class="bg-blue-500 dark:bg-blue-600 hover:bg-blue-600 dark:hover:bg-blue-700 transition-colors" 
                         style="width: {{ ($plannedTime / $totalSeconds) * 100 }}%" 
                         title="Заплановано: {{ $formatTime($plannedTime) }}">
                    </div>
                @endif
                @if($pausedTime > 0)
                    <div class="bg-gray-400 dark:bg-gray-500 hover:bg-gray-500 dark:hover:bg-gray-600 transition-colors" 
                         style="width: {{ ($pausedTime / $totalSeconds) * 100 }}%" 
                         title="На паузі: {{ $formatTime($pausedTime) }}">
                    </div>
                @endif
            </div>
        </div>
        
        <!-- Легенда -->
        <div class="flex flex-wrap justify-center gap-4 text-xs">
            @if($completedTime > 0)
                <div class="flex items-center gap-1.5 bg-green-50 dark:bg-green-950/30 px-3 py-1.5 rounded-full border border-green-200 dark:border-green-800">
                    <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                    <span class="text-gray-700 dark:text-gray-300 font-medium">Завершено</span>
                    <span class="text-gray-600 dark:text-gray-400">({{ $formatTime($completedTime) }})</span>
                </div>
            @endif
            @if($inProgressTime > 0)
                <div class="flex items-center gap-1.5 bg-amber-50 dark:bg-amber-950/30 px-3 py-1.5 rounded-full border border-amber-200 dark:border-amber-800">
                    <div class="w-3 h-3 bg-amber-500 rounded-full"></div>
                    <span class="text-gray-700 dark:text-gray-300 font-medium">В процесі</span>
                    <span class="text-gray-600 dark:text-gray-400">({{ $formatTime($inProgressTime) }})</span>
                </div>
            @endif
            @if($plannedTime > 0)
                <div class="flex items-center gap-1.5 bg-blue-50 dark:bg-blue-950/30 px-3 py-1.5 rounded-full border border-blue-200 dark:border-blue-800">
                    <div class="w-3 h-3 bg-blue-500 rounded-full"></div>
                    <span class="text-gray-700 dark:text-gray-300 font-medium">Заплановано</span>
                    <span class="text-gray-600 dark:text-gray-400">({{ $formatTime($plannedTime) }})</span>
                </div>
            @endif
            @if($pausedTime > 0)
                <div class="flex items-center gap-1.5 bg-gray-50 dark:bg-gray-900/30 px-3 py-1.5 rounded-full border border-gray-200 dark:border-gray-700">
                    <div class="w-3 h-3 bg-gray-400 rounded-full"></div>
                    <span class="text-gray-700 dark:text-gray-300 font-medium">На паузі</span>
                    <span class="text-gray-600 dark:text-gray-400">({{ $formatTime($pausedTime) }})</span>
                </div>
            @endif
        </div>
    @else
        <!-- Пустое состояние -->
        <div class="text-center py-12">
            <div class="text-6xl mb-3 opacity-50">⏰</div>
            <div class="text-lg text-gray-700 dark:text-gray-300 font-semibold mb-1">Записів часу ще немає</div>
            <div class="text-sm text-gray-500 dark:text-gray-400">Додайте перший запис, щоб почати облік часу</div>
        </div>
    @endif
</div>
