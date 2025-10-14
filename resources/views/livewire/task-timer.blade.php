<div class="task-timer"
     style="display: flex; flex-direction: column; align-items: center; gap: 12px;"
     x-data="{
        timerInterval: null,
        startTimer() {
            if (this.timerInterval) return;
            this.timerInterval = setInterval(() => {
                $wire.call('incrementTimer');
            }, 1000);
        },
        stopTimer() {
            if (this.timerInterval) {
                clearInterval(this.timerInterval);
                this.timerInterval = null;
            }
        }
     }"
     x-on:start-timer="startTimer()"
     x-on:stop-timer="stopTimer()">

    <!-- Прелоадер с котиком -->
    @if ($isLoading)
        <div class="timer-loader" style="display: flex; flex-direction: column; align-items: center; gap: 12px; padding: 20px;">
            <!-- Анимированный котик -->
            <div style="font-size: 48px; animation: bounce 1s infinite;">
                🐱
            </div>
            <div style="color: #666; font-size: 14px; font-weight: 500;">
                Завантаження...
            </div>
            <!-- CSS анимация -->
            <style>
                @keyframes bounce {
                    0%, 20%, 50%, 80%, 100% {
                        transform: translateY(0);
                    }
                    40% {
                        transform: translateY(-10px);
                    }
                    60% {
                        transform: translateY(-5px);
                    }
                }

                @keyframes spin {
                    0% { transform: rotate(0deg); }
                    100% { transform: rotate(360deg); }
                }

                .timer-loader {
                    min-height: 120px;
                    justify-content: center;
                }
            </style>
        </div>
    @else
        <!-- Основной таймер -->
        <div class="timer-display" style="font-size: 2em; font-family: monospace;">
            {{ $this->formattedTime }}
        </div>

        <!-- Кнопки управления -->
        <div style="display: flex; gap: 8px;">
            @if (!$isRunning && !$isPaused)
                <!-- Только кнопка Старт -->
                <button type="button"
                        wire:click="startTimer"
                        wire:loading.attr="disabled"
                        wire:loading.class="opacity-50"
                        class="timer-btn timer-btn-start"
                        style="background: #4caf50; color: #fff; padding: 8px 16px; border: none; border-radius: 6px; cursor: pointer; font-weight: 500;">
                    <span wire:loading.remove wire:target="startTimer">Старт</span>
                    <span wire:loading wire:target="startTimer">🐱 Завантаження...</span>
                </button>
            @elseif ($isPaused)
                <!-- Кнопки Продовжити и Стоп -->
                <button type="button"
                        wire:click="startTimer"
                        wire:loading.attr="disabled"
                        wire:loading.class="opacity-50"
                        class="timer-btn timer-btn-continue"
                        style="background: #4caf50; color: #fff; padding: 8px 16px; border: none; border-radius: 6px; cursor: pointer; font-weight: 500;">
                    <span wire:loading.remove wire:target="startTimer">Продовжити</span>
                    <span wire:loading wire:target="startTimer">🐱 Завантаження...</span>
                </button>
                <button type="button"
                        wire:click="stopTimer"
                        wire:loading.attr="disabled"
                        wire:loading.class="opacity-50"
                        class="timer-btn timer-btn-stop"
                        style="background: #f44336; color: #fff; padding: 8px 16px; border: none; border-radius: 6px; cursor: pointer; font-weight: 500;">
                    <span wire:loading.remove wire:target="stopTimer">Стоп</span>
                    <span wire:loading wire:target="stopTimer">🐱 Зберігаю...</span>
                </button>
            @elseif ($isRunning)
                <!-- Кнопки Пауза и Стоп -->
                <button type="button"
                        wire:click="pauseTimer"
                        wire:loading.attr="disabled"
                        wire:loading.class="opacity-50"
                        class="timer-btn timer-btn-pause"
                        style="background: #ff9800; color: #fff; padding: 8px 16px; border: none; border-radius: 6px; cursor: pointer; font-weight: 500;">
                    <span wire:loading.remove wire:target="pauseTimer">Пауза</span>
                    <span wire:loading wire:target="pauseTimer">🐱 Зберігаю...</span>
                </button>
                <button type="button"
                        wire:click="stopTimer"
                        wire:loading.attr="disabled"
                        wire:loading.class="opacity-50"
                        class="timer-btn timer-btn-stop"
                        style="background: #f44336; color: #fff; padding: 8px 16px; border: none; border-radius: 6px; cursor: pointer; font-weight: 500;">
                    <span wire:loading.remove wire:target="stopTimer">Стоп</span>
                    <span wire:loading wire:target="stopTimer">🐱 Зберігаю...</span>
                </button>
            @endif
        </div>

        <!-- Индикатор состояния -->
        @if ($isRunning)
            <div style="color: #4caf50; font-size: 0.875rem; font-weight: 500;">
                ⏱️ Таймер працює
            </div>
        @elseif ($isPaused)
            <div style="color: #ff9800; font-size: 0.875rem; font-weight: 500;">
                ⏸️ На паузі
            </div>
        @endif
    @endif
</div>
