<div class="task-timer"
     style="display: flex; flex-direction: column; align-items: center; gap: 12px;"
     x-data="{
        seconds: @entangle('seconds'),
        isRunning: @entangle('isRunning'),
        isPaused: @entangle('isPaused'),
        timerInterval: null,
        saveInterval: null,
        
        get formattedTime() {
            const hours = String(Math.floor(this.seconds / 3600)).padStart(2, '0');
            const minutes = String(Math.floor((this.seconds % 3600) / 60)).padStart(2, '0');
            const secs = String(this.seconds % 60).padStart(2, '0');
            return `${hours}:${minutes}:${secs}`;
        },
        
        startTimer() {
            if (this.timerInterval) return;
            
            // Счётчик секунд на клиенте - каждую секунду
            this.timerInterval = setInterval(() => {
                if (this.isRunning && !this.isPaused) {
                    this.seconds++;
                }
            }, 1000);
            
            // Автосохранение на сервер - каждую минуту
            this.saveInterval = setInterval(() => {
                if (this.isRunning && !this.isPaused) {
                    $wire.call('autoSave', this.seconds);
                }
            }, 60000);
        },
        
        stopTimer() {
            if (this.timerInterval) {
                clearInterval(this.timerInterval);
                this.timerInterval = null;
            }
            if (this.saveInterval) {
                clearInterval(this.saveInterval);
                this.saveInterval = null;
            }
        }
     }"
     x-on:start-timer="startTimer()"
     x-on:stop-timer="stopTimer()"
     x-init="if (isRunning) { startTimer(); }">

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
            <span x-text="formattedTime"></span>
        </div>

        <!-- Кнопки управления -->
        <div style="display: flex; gap: 8px;">
            <!-- Только кнопка Старт (когда не запущен и не на паузе) -->
            <button type="button"
                    x-show="!isRunning && !isPaused"
                    x-cloak
                    wire:click="startTimer"
                    wire:loading.attr="disabled"
                    wire:loading.class="opacity-50"
                    class="timer-btn timer-btn-start"
                    style="background: #4caf50; color: #fff; padding: 8px 16px; border: none; border-radius: 6px; cursor: pointer; font-weight: 500;">
                <span wire:loading.remove wire:target="startTimer">Старт</span>
                <span wire:loading wire:target="startTimer">🐱 Завантаження...</span>
            </button>

            <!-- Кнопка Продовжити (когда на паузе) -->
            <button type="button"
                    x-show="isPaused"
                    x-cloak
                    wire:click="startTimer"
                    wire:loading.attr="disabled"
                    wire:loading.class="opacity-50"
                    class="timer-btn timer-btn-continue"
                    style="background: #4caf50; color: #fff; padding: 8px 16px; border: none; border-radius: 6px; cursor: pointer; font-weight: 500;">
                <span wire:loading.remove wire:target="startTimer">Продовжити</span>
                <span wire:loading wire:target="startTimer">🐱 Завантаження...</span>
            </button>

            <!-- Кнопка Стоп (когда на паузе) -->
            <button type="button"
                    x-show="isPaused"
                    x-cloak
                    @click="stopTimer(); $wire.stopTimer()"
                    wire:loading.attr="disabled"
                    wire:loading.class="opacity-50"
                    class="timer-btn timer-btn-stop"
                    style="background: #f44336; color: #fff; padding: 8px 16px; border: none; border-radius: 6px; cursor: pointer; font-weight: 500;">
                <span wire:loading.remove wire:target="stopTimer">Стоп</span>
                <span wire:loading wire:target="stopTimer">🐱 Зберігаю...</span>
            </button>

            <!-- Кнопка Пауза (когда запущен) -->
            <button type="button"
                    x-show="isRunning"
                    x-cloak
                    @click="stopTimer(); $wire.pauseTimer()"
                    wire:loading.attr="disabled"
                    wire:loading.class="opacity-50"
                    class="timer-btn timer-btn-pause"
                    style="background: #ff9800; color: #fff; padding: 8px 16px; border: none; border-radius: 6px; cursor: pointer; font-weight: 500;">
                <span wire:loading.remove wire:target="pauseTimer">Пауза</span>
                <span wire:loading wire:target="pauseTimer">🐱 Зберігаю...</span>
            </button>

            <!-- Кнопка Стоп (когда запущен) -->
            <button type="button"
                    x-show="isRunning"
                    x-cloak
                    @click="stopTimer(); $wire.stopTimer()"
                    wire:loading.attr="disabled"
                    wire:loading.class="opacity-50"
                    class="timer-btn timer-btn-stop"
                    style="background: #f44336; color: #fff; padding: 8px 16px; border: none; border-radius: 6px; cursor: pointer; font-weight: 500;">
                <span wire:loading.remove wire:target="stopTimer">Стоп</span>
                <span wire:loading wire:target="stopTimer">🐱 Зберігаю...</span>
            </button>
        </div>

        <!-- Индикатор состояния -->
        <div x-show="isRunning" x-cloak style="color: #4caf50; font-size: 0.875rem; font-weight: 500;">
            ⏱️ Таймер працює
        </div>
        <div x-show="isPaused" x-cloak style="color: #ff9800; font-size: 0.875rem; font-weight: 500;">
            ⏸️ На паузі
        </div>
    @endif
</div>
