<div class="quick-timer-widget"
     style="padding: 20px; background: white; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);"
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
     x-on:start-quick-timer="startTimer()"
     x-on:stop-quick-timer="stopTimer()">

    <div style="display: flex; flex-direction: column; gap: 16px;">
        <!-- Заголовок -->
        <div style="display: flex; align-items: center; gap: 12px; padding-bottom: 12px; border-bottom: 2px solid #f3f4f6;">
            <div style="font-size: 24px;">⏱️</div>
            <h3 style="margin: 0; font-size: 18px; font-weight: 600; color: #1f2937;">Швидкий трекінг часу</h3>
        </div>

        @if ($isLoading)
            <!-- Прелоадер с котиком -->
            <div class="timer-loader" style="display: flex; flex-direction: column; align-items: center; gap: 12px; padding: 20px;">
                <div style="font-size: 48px; animation: bounce 1s infinite;">🐱</div>
                <div style="color: #666; font-size: 14px; font-weight: 500;">Завантаження...</div>
            </div>

            <style>
                @keyframes bounce {
                    0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
                    40% { transform: translateY(-10px); }
                    60% { transform: translateY(-5px); }
                }
            </style>
        @else
            @if (!$showConvertForm)
                <!-- Основной интерфейс таймера -->

                <!-- Поля ввода -->
                <div style="display: flex; flex-direction: column; gap: 12px;">
                    <div>
                        <label style="display: block; margin-bottom: 4px; font-size: 14px; font-weight: 500; color: #374151;">
                            Назва
                        </label>
                        <input type="text"
                               wire:model="title"
                               placeholder="Опишіть, що ви робите..."
                               style="width: 100%; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px;"
                               @if($isRunning) disabled @endif>
                    </div>

                    <div>
                        <label style="display: block; margin-bottom: 4px; font-size: 14px; font-weight: 500; color: #374151;">
                            Опис (необов'язково)
                        </label>
                        <textarea wire:model="description"
                                  placeholder="Додаткові деталі..."
                                  rows="2"
                                  style="width: 100%; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; resize: vertical;"
                                  @if($isRunning) disabled @endif></textarea>
                    </div>
                </div>

                <!-- Дисплей таймера -->
                <div style="text-align: center; padding: 24px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                    <div style="font-size: 48px; font-family: monospace; font-weight: bold; color: white; text-shadow: 2px 2px 4px rgba(0,0,0,0.2);">
                        {{ $this->formattedTime }}
                    </div>
                    @if ($activeTime)
                        <div style="margin-top: 8px; font-size: 12px; color: rgba(255,255,255,0.9);">
                            Витрачено: {{ number_format($seconds / 3600, 2) }} год
                        </div>
                    @endif
                </div>

                <!-- Кнопки управления -->
                <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                    @if (!$isRunning && !$isPaused)
                        <!-- Только кнопка Старт -->
                        <button type="button"
                                wire:click="startTimer"
                                wire:loading.attr="disabled"
                                wire:loading.class="opacity-50"
                                class="timer-btn"
                                style="flex: 1; background: #10b981; color: #fff; padding: 12px 20px; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 16px; transition: all 0.3s;">
                            <span wire:loading.remove wire:target="startTimer">▶️ Старт</span>
                            <span wire:loading wire:target="startTimer">🐱 Завантаження...</span>
                        </button>
                    @elseif ($isPaused)
                        <!-- Кнопки Продовжити, Стоп и Конвертувати -->
                        <button type="button"
                                wire:click="startTimer"
                                wire:loading.attr="disabled"
                                wire:loading.class="opacity-50"
                                class="timer-btn"
                                style="flex: 1; background: #10b981; color: #fff; padding: 12px 20px; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 16px;">
                            <span wire:loading.remove wire:target="startTimer">▶️ Продовжити</span>
                            <span wire:loading wire:target="startTimer">🐱 Завантаження...</span>
                        </button>
                        <button type="button"
                                wire:click="stopTimer"
                                wire:loading.attr="disabled"
                                wire:loading.class="opacity-50"
                                class="timer-btn"
                                style="flex: 1; background: #ef4444; color: #fff; padding: 12px 20px; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 16px;">
                            <span wire:loading.remove wire:target="stopTimer">⏹️ Стоп</span>
                            <span wire:loading wire:target="stopTimer">🐱 Зберігаю...</span>
                        </button>
                        <button type="button"
                                wire:click="showConvertToTask"
                                wire:loading.attr="disabled"
                                wire:loading.class="opacity-50"
                                class="timer-btn"
                                style="width: 100%; background: #3b82f6; color: #fff; padding: 12px 20px; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 16px;">
                            <span wire:loading.remove wire:target="showConvertToTask">🔄 Конвертувати в завдання</span>
                            <span wire:loading wire:target="showConvertToTask">🐱 Завантаження...</span>
                        </button>
                    @elseif ($isRunning)
                        <!-- Кнопка Пауза -->
                        <button type="button"
                                wire:click="pauseTimer"
                                wire:loading.attr="disabled"
                                wire:loading.class="opacity-50"
                                class="timer-btn"
                                style="width: 100%; background: #f59e0b; color: #fff; padding: 12px 20px; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 16px;">
                            <span wire:loading.remove wire:target="pauseTimer">⏸️ Пауза</span>
                            <span wire:loading wire:target="pauseTimer">🐱 Зберігаю...</span>
                        </button>
                    @endif
                </div>
            @else
                <!-- Форма конвертации в задачу -->
                <div style="padding: 20px; background: #f9fafb; border-radius: 8px; border: 2px solid #e5e7eb;">
                    <h4 style="margin: 0 0 16px 0; font-size: 16px; font-weight: 600; color: #1f2937;">
                        Конвертувати в завдання
                    </h4>

                    <div style="margin-bottom: 16px;">
                        <div style="padding: 12px; background: white; border-radius: 6px; border: 1px solid #d1d5db;">
                            <div style="font-size: 14px; color: #6b7280; margin-bottom: 4px;">Поточний час:</div>
                            <div style="font-size: 24px; font-family: monospace; font-weight: bold; color: #1f2937;">
                                {{ $this->formattedTime }}
                            </div>
                        </div>
                    </div>

                    <div style="margin-bottom: 16px;">
                        <label style="display: block; margin-bottom: 8px; font-size: 14px; font-weight: 500; color: #374151;">
                            Виберіть завдання
                        </label>
                        <select wire:model="selectedTaskId"
                                style="width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px;">
                            <option value="">-- Оберіть завдання --</option>
                            @foreach($availableTasks as $task)
                                <option value="{{ $task['id'] }}">{{ $task['label'] }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div style="display: flex; gap: 8px;">
                        <button type="button"
                                wire:click="convertToTask"
                                wire:loading.attr="disabled"
                                wire:loading.class="opacity-50"
                                style="flex: 1; background: #10b981; color: #fff; padding: 10px 16px; border: none; border-radius: 6px; cursor: pointer; font-weight: 500;">
                            <span wire:loading.remove wire:target="convertToTask">✅ Конвертувати</span>
                            <span wire:loading wire:target="convertToTask">🐱 Конвертую...</span>
                        </button>
                        <button type="button"
                                wire:click="cancelConvert"
                                style="flex: 1; background: #6b7280; color: #fff; padding: 10px 16px; border: none; border-radius: 6px; cursor: pointer; font-weight: 500;">
                            Скасувати
                        </button>
                    </div>
                </div>
            @endif
        @endif
    </div>

    <style>
        .timer-btn:hover:not(:disabled) {
            opacity: 0.9;
            transform: translateY(-1px);
            box-shadow: 0 4px 6px rgba(0,0,0,0.15);
        }

        .timer-btn:active:not(:disabled) {
            transform: translateY(0);
        }
    </style>
</div>

